<?php declare(strict_types = 1);

namespace Tests\Unit\Flow;

use DateTimeImmutable;
use Shredio\Commentator\Flow\ThreadFlow;
use Shredio\Commentator\Input\CommentInput;
use Shredio\Commentator\Input\ThreadInput;
use Shredio\Commentator\ValueObject\Author;
use Tests\TestCase;

final class ThreadFlowTest extends TestCase
{

	public function testSimpleThreadWithNoComments(): void
	{
		$author = new Author('1', 'testuser');
		$input = new ThreadInput(
			content: 'Hello Investors! This is my main post.',
			author: $author,
			createdAt: new DateTimeImmutable('2025-01-15 12:00:00'),
		);

		$result = ThreadFlow::buildHierarchy($input);

		$this->assertStringEqualsFile(__DIR__ . '/expected/simple_thread_no_comments.txt', $result . "\n");
	}

	public function testThreadWithSingleLevelComments(): void
	{
		$author = new Author('1', 'testuser');
		$commenter = new Author('2', 'commenter');
		
		$comment1 = new CommentInput(
			content: 'Great post!',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 12:30:00'),
		);
		
		$comment2 = new CommentInput(
			content: 'I agree completely.',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 13:00:00'),
		);

		$input = new ThreadInput(
			content: 'Hello Investors! This is my main post.',
			author: $author,
			createdAt: new DateTimeImmutable('2025-01-15 12:00:00'),
			comments: [$comment1, $comment2],
		);

		$result = ThreadFlow::buildHierarchy($input);
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/single_level_comments.txt', $result . "\n");
	}

	public function testThreadWithNestedComments(): void
	{
		$author = new Author('1', 'testuser');
		$commenter = new Author('2', 'commenter');
		
		$nestedComment = new CommentInput(
			content: 'Thanks for clarifying!',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 13:30:00'),
		);
		
		$comment1 = new CommentInput(
			content: 'What do you think about this?',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 12:30:00'),
			comments: [$nestedComment],
		);
		
		$comment2 = new CommentInput(
			content: 'Interesting perspective.',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 14:00:00'),
		);

		$input = new ThreadInput(
			content: 'Hello Investors! This is my main post.',
			author: $author,
			createdAt: new DateTimeImmutable('2025-01-15 12:00:00'),
			comments: [$comment1, $comment2],
		);

		$result = ThreadFlow::buildHierarchy($input);
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/nested_comments.txt', $result . "\n");
	}

	public function testDeeplyNestedComments(): void
	{
		$author = new Author('1', 'testuser');
		$commenter = new Author('2', 'commenter');
		
		$deepComment = new CommentInput(
			content: 'Level 3 comment',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 13:30:00'),
		);
		
		$nestedComment = new CommentInput(
			content: 'Level 2 comment',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 13:00:00'),
			comments: [$deepComment],
		);
		
		$comment1 = new CommentInput(
			content: 'Level 1 comment',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 12:30:00'),
			comments: [$nestedComment],
		);

		$input = new ThreadInput(
			content: 'Main post content',
			author: $author,
			createdAt: new DateTimeImmutable('2025-01-15 12:00:00'),
			comments: [$comment1],
		);

		$result = ThreadFlow::buildHierarchy($input);
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/deeply_nested_comments.txt', $result . "\n");
	}

	public function testHtmlContentNormalization(): void
	{
		$author = new Author('1', 'testuser');
		$commenter = new Author('2', 'commenter');
		
		$comment = new CommentInput(
			content: '<p>This is a <strong>formatted</strong> comment.</p><p>With multiple paragraphs.</p>',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 12:30:00'),
		);

		$input = new ThreadInput(
			content: '<p>Hello <em>Investors</em>!</p><p>This is my post with <a href="#">links</a>.</p>',
			author: $author,
			createdAt: new DateTimeImmutable('2025-01-15 12:00:00'),
			comments: [$comment],
		);

		$result = ThreadFlow::buildHierarchy($input);

		$this->assertStringEqualsFile(__DIR__ . '/expected/html_normalization.txt', $result . "\n");
	}

	public function testMultipleCommentsWithSiblings(): void
	{
		$author = new Author('1', 'testuser');
		$commenter = new Author('2', 'commenter');
		
		$comment1 = new CommentInput(
			content: 'First comment',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 12:30:00'),
		);
		
		$comment2 = new CommentInput(
			content: 'Second comment',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 13:00:00'),
		);
		
		$comment3 = new CommentInput(
			content: 'Third comment',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 13:30:00'),
		);

		$input = new ThreadInput(
			content: 'Main post',
			author: $author,
			createdAt: new DateTimeImmutable('2025-01-15 12:00:00'),
			comments: [$comment1, $comment2, $comment3],
		);

		$result = ThreadFlow::buildHierarchy($input);
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/multiple_comments_siblings.txt', $result . "\n");
	}

	public function testComplexNestedStructure(): void
	{
		$author = new Author('1', 'testuser');
		$commenter = new Author('2', 'commenter');
		
		$reply1_1_1 = new CommentInput(
			content: 'Deep reply',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 14:00:00'),
		);
		
		$reply1_1 = new CommentInput(
			content: 'Reply to first',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 13:30:00'),
			comments: [$reply1_1_1],
		);
		
		$comment1 = new CommentInput(
			content: 'First comment',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 12:30:00'),
			comments: [$reply1_1],
		);
		
		$reply2_1 = new CommentInput(
			content: 'Reply to second',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 13:15:00'),
		);
		
		$comment2 = new CommentInput(
			content: 'Second comment',
			author: $commenter,
			createdAt: new DateTimeImmutable('2025-01-15 13:00:00'),
			comments: [$reply2_1],
		);

		$input = new ThreadInput(
			content: 'Main post content',
			author: $author,
			createdAt: new DateTimeImmutable('2025-01-15 12:00:00'),
			comments: [$comment1, $comment2],
		);

		$result = ThreadFlow::buildHierarchy($input);
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/complex_nested_structure.txt', $result . "\n");
	}

}
