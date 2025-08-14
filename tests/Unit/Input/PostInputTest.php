<?php declare(strict_types = 1);

namespace Tests\Unit\Input;

use DateTimeImmutable;
use Shredio\Commentator\Input\CommentInput;
use Shredio\Commentator\Input\PostInput;
use Shredio\Commentator\Input\ThreadInput;
use Shredio\Commentator\ValueObject\Author;
use Tests\TestCase;

final class PostInputTest extends TestCase
{

	private function createAuthor(string $id = 'test_id', string $nickname = 'Test Author'): Author
	{
		return new Author($id, $nickname);
	}

	private function createDatetime(string $datetime = '2025-01-01 12:00:00'): DateTimeImmutable
	{
		return new DateTimeImmutable($datetime);
	}

	/**
	 * @param list<CommentInput> $comments
	 */
	private function createThreadInput(
		string $content = 'Thread content',
		?Author $author = null,
		?DateTimeImmutable $createdAt = null,
		array $comments = []
	): ThreadInput {
		return new ThreadInput(
			$content,
			$author ?? $this->createAuthor(),
			$createdAt ?? $this->createDatetime(),
			$comments
		);
	}

	/**
	 * @param list<CommentInput> $comments
	 */
	private function createCommentInput(
		string $content = 'Comment content',
		?Author $author = null,
		?DateTimeImmutable $createdAt = null,
		array $comments = []
	): CommentInput {
		return new CommentInput(
			$content,
			$author ?? $this->createAuthor('comment_author', 'Comment Author'),
			$createdAt ?? $this->createDatetime('2025-01-01 13:00:00'),
			$comments
		);
	}

	public function testGetItemCountForPostWithoutComments(): void
	{
		$post = $this->createThreadInput('Test content');

		$itemCount = $post->getItemCount();

		$this->assertSame(1, $itemCount);
	}

	public function testGetItemCountForPostWithOneComment(): void
	{
		$comment = $this->createCommentInput('Single comment');
		$post = $this->createThreadInput('Test content', null, null, [$comment]);

		$itemCount = $post->getItemCount();

		$this->assertSame(2, $itemCount);
	}

	public function testGetItemCountForPostWithMultipleComments(): void
	{
		$comment1 = $this->createCommentInput('First comment');
		$comment2 = $this->createCommentInput('Second comment');
		$comment3 = $this->createCommentInput('Third comment');
		$post = $this->createThreadInput('Test content', null, null, [$comment1, $comment2, $comment3]);

		$itemCount = $post->getItemCount();

		$this->assertSame(4, $itemCount);
	}

	public function testGetItemCountForPostWithNestedComments(): void
	{
		$nestedComment = $this->createCommentInput('Nested comment');
		$parentComment = $this->createCommentInput('Parent comment', null, null, [$nestedComment]);
		$post = $this->createThreadInput('Test content', null, null, [$parentComment]);

		$itemCount = $post->getItemCount();

		$this->assertSame(3, $itemCount);
	}

	public function testGetItemCountForPostWithDeeplyNestedComments(): void
	{
		// Level 3: deepest comment
		$level3Comment = $this->createCommentInput('Level 3 comment');
		
		// Level 2: comment with nested comment
		$level2Comment = $this->createCommentInput('Level 2 comment', null, null, [$level3Comment]);
		
		// Level 1: comment with nested comment
		$level1Comment = $this->createCommentInput('Level 1 comment', null, null, [$level2Comment]);
		
		// Main post with nested structure
		$post = $this->createThreadInput('Main post', null, null, [$level1Comment]);

		$itemCount = $post->getItemCount();

		$this->assertSame(4, $itemCount);
	}

	public function testGetItemCountForComplexNestedStructure(): void
	{
		// Create a complex nested structure:
		// Post (1)
		// ├── Comment 1 (2)
		// │   ├── Comment 1.1 (3)
		// │   └── Comment 1.2 (4)
		// └── Comment 2 (5)
		//     └── Comment 2.1 (6)
		//         └── Comment 2.1.1 (7)

		$comment1_1 = $this->createCommentInput('Comment 1.1');
		$comment1_2 = $this->createCommentInput('Comment 1.2');
		$comment1 = $this->createCommentInput('Comment 1', null, null, [$comment1_1, $comment1_2]);

		$comment2_1_1 = $this->createCommentInput('Comment 2.1.1');
		$comment2_1 = $this->createCommentInput('Comment 2.1', null, null, [$comment2_1_1]);
		$comment2 = $this->createCommentInput('Comment 2', null, null, [$comment2_1]);

		$post = $this->createThreadInput('Main post', null, null, [$comment1, $comment2]);

		$itemCount = $post->getItemCount();

		$this->assertSame(7, $itemCount);
	}

	public function testGetItemCountForEmptyCommentsList(): void
	{
		$post = $this->createThreadInput('Test content', null, null, []);

		$itemCount = $post->getItemCount();

		$this->assertSame(1, $itemCount);
	}

	public function testGetItemCountWorksWithCommentInput(): void
	{
		$nestedComment = $this->createCommentInput('Nested comment');
		$comment = $this->createCommentInput('Parent comment', null, null, [$nestedComment]);

		$itemCount = $comment->getItemCount();

		$this->assertSame(2, $itemCount);
	}

	public function testGetItemCountWithMixedCommentDepths(): void
	{
		// Structure:
		// Comment (1)
		// ├── Nested Comment 1 (2)
		// ├── Nested Comment 2 (3)
		// │   └── Deep Comment (4)
		// └── Nested Comment 3 (5)

		$deepComment = $this->createCommentInput('Deep comment');
		$nestedComment1 = $this->createCommentInput('Nested comment 1');
		$nestedComment2 = $this->createCommentInput('Nested comment 2', null, null, [$deepComment]);
		$nestedComment3 = $this->createCommentInput('Nested comment 3');
		
		$comment = $this->createCommentInput(
			'Main comment', 
			null, 
			null, 
			[$nestedComment1, $nestedComment2, $nestedComment3]
		);

		$itemCount = $comment->getItemCount();

		$this->assertSame(5, $itemCount);
	}

	public function testGetMinimumAndMaximumDatesWithoutComments(): void
	{
		$postDate = $this->createDatetime('2025-01-15 10:00:00');
		$post = $this->createThreadInput('Test content', null, $postDate);

		[$min, $max] = $post->getMinimumAndMaximumDates();

		$this->assertEquals($postDate, $min);
		$this->assertEquals($postDate, $max);
	}

	public function testGetMinimumAndMaximumDatesWithOneComment(): void
	{
		$postDate = $this->createDatetime('2025-01-15 10:00:00');
		$commentDate = $this->createDatetime('2025-01-15 11:00:00');
		
		$comment = $this->createCommentInput('Comment', null, $commentDate);
		$post = $this->createThreadInput('Test content', null, $postDate, [$comment]);

		[$min, $max] = $post->getMinimumAndMaximumDates();

		$this->assertEquals($postDate, $min);
		$this->assertEquals($commentDate, $max);
	}

	public function testGetMinimumAndMaximumDatesWithCommentBeforePost(): void
	{
		$postDate = $this->createDatetime('2025-01-15 10:00:00');
		$commentDate = $this->createDatetime('2025-01-15 09:00:00');
		
		$comment = $this->createCommentInput('Comment', null, $commentDate);
		$post = $this->createThreadInput('Test content', null, $postDate, [$comment]);

		[$min, $max] = $post->getMinimumAndMaximumDates();

		$this->assertEquals($commentDate, $min);
		$this->assertEquals($postDate, $max);
	}

	public function testGetMinimumAndMaximumDatesWithMultipleComments(): void
	{
		$postDate = $this->createDatetime('2025-01-15 10:00:00');
		$earliestDate = $this->createDatetime('2025-01-15 08:00:00');
		$middleDate = $this->createDatetime('2025-01-15 12:00:00');
		$latestDate = $this->createDatetime('2025-01-15 14:00:00');
		
		$comment1 = $this->createCommentInput('Comment 1', null, $earliestDate);
		$comment2 = $this->createCommentInput('Comment 2', null, $middleDate);
		$comment3 = $this->createCommentInput('Comment 3', null, $latestDate);
		
		$post = $this->createThreadInput('Test content', null, $postDate, [$comment1, $comment2, $comment3]);

		[$min, $max] = $post->getMinimumAndMaximumDates();

		$this->assertEquals($earliestDate, $min);
		$this->assertEquals($latestDate, $max);
	}

	public function testGetMinimumAndMaximumDatesWithNestedComments(): void
	{
		$postDate = $this->createDatetime('2025-01-15 10:00:00');
		$parentCommentDate = $this->createDatetime('2025-01-15 11:00:00');
		$nestedCommentDate = $this->createDatetime('2025-01-15 09:00:00');
		
		$nestedComment = $this->createCommentInput('Nested comment', null, $nestedCommentDate);
		$parentComment = $this->createCommentInput('Parent comment', null, $parentCommentDate, [$nestedComment]);
		$post = $this->createThreadInput('Test content', null, $postDate, [$parentComment]);

		[$min, $max] = $post->getMinimumAndMaximumDates();

		$this->assertEquals($nestedCommentDate, $min);
		$this->assertEquals($parentCommentDate, $max);
	}

}