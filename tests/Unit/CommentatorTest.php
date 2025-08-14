<?php declare(strict_types = 1);

namespace Tests\Unit;

use DateTimeImmutable;
use InvalidArgumentException;
use Shredio\Commentator\Commentator;
use Shredio\Commentator\CommentatorContentProcessor;
use Shredio\Commentator\Input\CommentInput;
use Shredio\Commentator\Input\PostInput;
use Shredio\Commentator\Input\ThreadInput;
use Shredio\Commentator\MentionProcessor;
use Shredio\Commentator\ValueObject\Author;
use Tests\Common\FixedDateTimeGeneratorFactory;
use Tests\Common\SequentialAuthorAllocator;
use Tests\TestCase;

final class CommentatorTest extends TestCase
{
    private function createTestPreprocessor(string $prefix): CommentatorContentProcessor
    {
        return new class($prefix) implements CommentatorContentProcessor {
            public function __construct(private string $prefix) {}
            
            public function preprocess(Commentator $commentator, ThreadInput $thread, string $content, PostInput $input): ?string
            {
                return $this->prefix . $content;
            }
            
            public function postprocess(Commentator $commentator, ThreadInput $thread, string $content, PostInput $input, Author $author): ?string
            {
                return $content;
            }
        };
    }
    
    private function createTestPostprocessor(string $suffix): CommentatorContentProcessor
    {
        return new class($suffix) implements CommentatorContentProcessor {
            public function __construct(private string $suffix) {}
            
            public function preprocess(Commentator $commentator, ThreadInput $thread, string $content, PostInput $input): ?string
            {
                return $content;
            }
            
            public function postprocess(Commentator $commentator, ThreadInput $thread, string $content, PostInput $input, Author $author): ?string
            {
                return $content . $this->suffix;
            }
        };
    }
    
    private function createAuthorTrackingProcessor(): CommentatorContentProcessor
    {
        return new class implements CommentatorContentProcessor {
            public function preprocess(Commentator $commentator, ThreadInput $thread, string $content, PostInput $input): ?string
            {
                return $content;
            }
            
            public function postprocess(Commentator $commentator, ThreadInput $thread, string $content, PostInput $input, Author $author): ?string
            {
                return $content . ' (by ' . $author->nickname . ')';
            }
        };
    }

    private function createNullReturningPreprocessor(): CommentatorContentProcessor
    {
        return new class implements CommentatorContentProcessor {
            public function preprocess(Commentator $commentator, ThreadInput $thread, string $content, PostInput $input): ?string
            {
                return null;
            }
            
            public function postprocess(Commentator $commentator, ThreadInput $thread, string $content, PostInput $input, Author $author): ?string
            {
                return $content;
            }
        };
    }

    private function createNullReturningPostprocessor(): CommentatorContentProcessor
    {
        return new class implements CommentatorContentProcessor {
            public function preprocess(Commentator $commentator, ThreadInput $thread, string $content, PostInput $input): ?string
            {
                return $content;
            }
            
            public function postprocess(Commentator $commentator, ThreadInput $thread, string $content, PostInput $input, Author $author): ?string
            {
                return null;
            }
        };
    }

	/**
	 * @return list<Author>
	 */
    private function createAuthors(int $count): array
    {
        $authors = [];
        for ($i = 1; $i <= $count; $i++) {
            $authors[] = new Author("author{$i}", "Author {$i}");
        }
        return $authors;
    }

	/**
	 * @param list<CommentInput> $comments
	 */
    private function createThreadInput(
        string $content = 'Test content',
        ?Author $author = null,
        ?DateTimeImmutable $createdAt = null,
        array $comments = []
    ): ThreadInput {
        return new ThreadInput(
            $content,
            $author ?? new Author('main_author', 'Main Author'),
            $createdAt ?? new DateTimeImmutable('2025-01-01 12:00:00'),
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
            $author ?? new Author('comment_author', 'Comment Author'),
            $createdAt ?? new DateTimeImmutable('2025-01-01 13:00:00'),
            $comments
        );
    }

    public function testConstructorWithTooFewAuthors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least 5 authors must be provided for allocation.');

        new SequentialAuthorAllocator($this->createAuthors(4));
    }

    public function testSameMentions(): void
    {
		$commentator = new Commentator(
			$this->createMessageCapturingAiService(),
			'test-model',
			'Test instruction with {locale}',
			'en',
			new SequentialAuthorAllocator($this->createAuthors(5)),
			new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00')),
			[new MentionProcessor()],
		);

		$output = $commentator->comment($this->createThreadInput(
			author: new Author('212', 'lilaframi'),
			comments: [
				$this->createCommentInput('<!-- version: 1 --><p dir="ltr"><span style="white-space: pre-wrap;">Hey investor! <a class="mention-account" data-mention="@lilaframi" data-user="212">@lilaframi</a></span></p>'),
				$this->createCommentInput('<!-- version: 1 --><p dir="ltr"><span style="white-space: pre-wrap;">Hey investor! <a class="mention-account" data-mention="@lilaframi" data-user="212">@lilaframi</a></span></p>'),
			],
		));

		$this->assertCount(2, $output->comments);
		$this->assertSame('**Target locale:** "en"
**The resulting content must be in following gender:** "M"
**Hierarchy:**
"""
@Author 1 (M) [2025-01-01]: **Target locale:** "en"
                            **The resulting content must be in following gender:** "M"
                            **Content to process:**
                            """
                            Test content"""
└── **Current content of post you must translate**
"""
**Content to process:**
"""
<!-- version: 1 --><p dir="ltr"><span style="white-space: pre-wrap;">Hey investor! <a class="mention-account" data-mention="@Author 1" data-user="author1">@Author 1</a></span></p>"""
', $output->comments[0]->content);
		$this->assertSame('**Target locale:** "en"
**The resulting content must be in following gender:** "M"
**Hierarchy:**
"""
@Author 1 (M) [2025-01-01]: **Target locale:** "en"
                            **The resulting content must be in following gender:** "M"
                            **Content to process:**
                            """
                            Test content"""
├── @Author 2 (M) [2025-01-01]: **Target locale:** "en"
│                               **The resulting content must be in following gender:** "M"
│                               **Hierarchy:**
│                               """
│                               @Author 1 (M) [2025-01-01]: **Target locale:** "en"
│                               **The resulting content must be in following gender:** "M"
│                               **Content to process:**
│                               """
│                               Test content"""
│                               └── **Current content of post you must translate**
│                               """
│                               **Content to process:**
│                               """
│                               Hey investor! @Author 1
│                               """
└── **Current content of post you must translate**
"""
**Content to process:**
"""
<!-- version: 1 --><p dir="ltr"><span style="white-space: pre-wrap;">Hey investor! <a class="mention-account" data-mention="@Author 1" data-user="author1">@Author 1</a></span></p>"""
', $output->comments[1]->content);
    }

    public function testCommentWithValidThread(): void
    {
        $commentator = new Commentator(
            $this->createAiService('Processed content'),
            'test-model',
            'Test instruction with {locale}',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00'))
        );

        $thread = $this->createThreadInput('Original content');
        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        $this->assertSame('Processed content', $result->content);
        $this->assertInstanceOf(DateTimeImmutable::class, $result->createdAt);
    }

    public function testCommentWithEmptyContent(): void
    {
        $commentator = new Commentator(
            $this->createAiService(),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00'))
        );

        $thread = $this->createThreadInput('');
        $result = $commentator->comment($thread);

        $this->assertNull($result);
    }

    public function testCommentWithWhitespaceOnlyContent(): void
    {
        $commentator = new Commentator(
            $this->createAiService(),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00'))
        );

        $thread = $this->createThreadInput('   ');
        $result = $commentator->comment($thread);

        $this->assertNull($result);
    }

    public function testCommentWithNestedComments(): void
    {
        $commentator = new Commentator(
            $this->createAiService('Processed content'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(10)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00'))
        );

        $nestedComment = $this->createCommentInput('Nested comment');
        $comment = $this->createCommentInput('Main comment', null, null, [$nestedComment]);
        $thread = $this->createThreadInput('Thread content', null, null, [$comment]);

        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        $this->assertCount(1, $result->comments);
        $this->assertSame('Processed content', $result->comments[0]->content);
        $this->assertCount(1, $result->comments[0]->comments);
        $this->assertSame('Processed content', $result->comments[0]->comments[0]->content);
    }

    public function testAuthorAllocation(): void
    {
        $commentator = new Commentator(
            $this->createAiService('Processed content'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00'))
        );

        $comment1 = $this->createCommentInput('Comment 1', new Author('user1', 'User 1'));
        $comment2 = $this->createCommentInput('Comment 2', new Author('user1', 'User 1'));
        $comment3 = $this->createCommentInput('Comment 3', new Author('user2', 'User 2'));
        $thread = $this->createThreadInput('Thread', null, null, [$comment1, $comment2, $comment3]);

        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        $this->assertCount(3, $result->comments);
        
        // With SequentialAuthorAllocator, same original author should get same allocated author
        $this->assertSame($result->comments[0]->author->id, $result->comments[1]->author->id);
        
        // Different original author should get different allocated author
        $this->assertNotSame($result->comments[0]->author->id, $result->comments[2]->author->id);
    }

    public function testAuthorExhaustion(): void
    {
        $commentator = new Commentator(
            $this->createAiService('Processed content'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00'))
        );

        $comments = [];
        for ($i = 1; $i <= 7; $i++) {
            $comments[] = $this->createCommentInput("Comment {$i}", new Author("user{$i}", "User {$i}"));
        }
        $thread = $this->createThreadInput('Thread', null, null, $comments);

        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        $this->assertCount(4, $result->comments);
    }

    public function testSystemInstructionLocaleReplacement(): void
    {
        $commentator = new Commentator(
            $this->createAiService('Translated content'),
            'test-model',
            'Translate to {locale} locale',
            'czech',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00'))
        );

        $thread = $this->createThreadInput('Test content');
        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        $this->assertSame('Translated content', $result->content);
    }

    public function testTargetDateGeneration(): void
    {
        $fixedDateTime = new DateTimeImmutable('2025-01-01 15:00:00');
        $commentator = new Commentator(
            $this->createAiService('Processed content'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory($fixedDateTime)
        );

        $thread = $this->createThreadInput('Test content');
        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        
        // Target date should be the fixed date from FixedDateTimeGeneratorFactory
        $this->assertEquals($fixedDateTime, $result->createdAt);
    }

    public function testContentPreprocessing(): void
    {
        $preprocessor = $this->createTestPreprocessor('[PRE] ');
        
        $commentator = new Commentator(
            $this->createAiService('AI Response'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00')),
            [$preprocessor]
        );

        $thread = $this->createThreadInput('Original content');
        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        $this->assertSame('AI Response', $result->content);
    }

    public function testContentPostprocessing(): void
    {
        $postprocessor = $this->createTestPostprocessor(' [POST]');
        
        $commentator = new Commentator(
            $this->createAiService('AI Response'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00')),
            [$postprocessor]
        );

        $thread = $this->createThreadInput('Original content');
        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        $this->assertSame('AI Response [POST]', $result->content);
    }

    public function testAuthorTrackingInPostprocess(): void
    {
        $authorTracker = $this->createAuthorTrackingProcessor();
        
        $commentator = new Commentator(
            $this->createAiService('AI Response'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00')),
            [$authorTracker]
        );

        $thread = $this->createThreadInput('Original content');
        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        $this->assertSame('AI Response (by Author 1)', $result->content);
    }

    public function testMultipleProcessorsChain(): void
    {
        $preprocessor = $this->createTestPreprocessor('[PRE] ');
        $postprocessor = $this->createTestPostprocessor(' [POST]');
        $authorTracker = $this->createAuthorTrackingProcessor();
        
        $commentator = new Commentator(
            $this->createAiService('AI Response'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00')),
            [$preprocessor, $postprocessor, $authorTracker]
        );

        $thread = $this->createThreadInput('Original content');
        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        // Postprocessors run in sequence: first postprocessor adds ' [POST]', then authorTracker adds ' (by Author 1)'
        $this->assertSame('AI Response [POST] (by Author 1)', $result->content);
    }

    public function testProcessorsWithComments(): void
    {
        $authorTracker = $this->createAuthorTrackingProcessor();
        
        $commentator = new Commentator(
            $this->createAiService('AI Response'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00')),
            [$authorTracker]
        );

        $comment1 = $this->createCommentInput('Comment 1', new Author('user1', 'User 1'));
        $comment2 = $this->createCommentInput('Comment 2', new Author('user2', 'User 2'));
        $thread = $this->createThreadInput('Thread content', null, null, [$comment1, $comment2]);

        $result = $commentator->comment($thread);

        $this->assertNotNull($result);
        $this->assertCount(2, $result->comments);
        
        // Thread gets first allocated author
        $this->assertSame('AI Response (by Author 1)', $result->content);
        
        // Comments get sequentially allocated authors
        $this->assertSame('AI Response (by Author 2)', $result->comments[0]->content);
        $this->assertSame('AI Response (by Author 3)', $result->comments[1]->content);
    }

    public function testPreprocessorReturningNullSkipsProcessing(): void
    {
        $nullPreprocessor = $this->createNullReturningPreprocessor();
        
        $commentator = new Commentator(
            $this->createAiService('AI Response'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00')),
            [$nullPreprocessor]
        );

        $thread = $this->createThreadInput('Original content');
        $result = $commentator->comment($thread);

        $this->assertNull($result);
    }

    public function testPostprocessorReturningNullSkipsProcessing(): void
    {
        $nullPostprocessor = $this->createNullReturningPostprocessor();
        
        $commentator = new Commentator(
            $this->createAiService('AI Response'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00')),
            [$nullPostprocessor]
        );

        $thread = $this->createThreadInput('Original content');
        $result = $commentator->comment($thread);

        $this->assertNull($result);
    }

    public function testNullPreprocessorWithComments(): void
    {
        $nullPreprocessor = $this->createNullReturningPreprocessor();
        
        $commentator = new Commentator(
            $this->createAiService('AI Response'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(5)),
            new FixedDateTimeGeneratorFactory(new DateTimeImmutable('2025-01-01 15:00:00')),
            [$nullPreprocessor]
        );

        $comment = $this->createCommentInput('Comment content');
        $thread = $this->createThreadInput('Thread content', null, null, [$comment]);
        $result = $commentator->comment($thread);

        // Thread should be null, so entire result should be null
        $this->assertNull($result);
    }
}
