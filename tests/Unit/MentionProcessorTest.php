<?php declare(strict_types = 1);

namespace Tests\Unit;

use DateTimeImmutable;
use Shredio\Commentator\Commentator;
use Shredio\Commentator\Input\CommentInput;
use Shredio\Commentator\Input\ThreadInput;
use Shredio\Commentator\MentionProcessor;
use Shredio\Commentator\ValueObject\Author;
use Tests\Common\FixedDateTimeGenerator;
use Tests\Common\SequentialAuthorAllocator;
use Tests\TestCase;

final class MentionProcessorTest extends TestCase
{
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

    private function createCommentator(): Commentator
    {
        return new Commentator(
            $this->createAiService('AI Response'),
            'test-model',
            'Test instruction',
            'en',
            new SequentialAuthorAllocator($this->createAuthors(10)),
            new FixedDateTimeGenerator(new DateTimeImmutable('2025-01-01 15:00:00'))
        );
    }

    /**
     * @param list<CommentInput> $comments
     */
    private function createThreadInput(string $content = 'Test content', ?Author $author = null, array $comments = []): ThreadInput
    {
        return new ThreadInput(
            $content,
            $author ?? new Author('main_author', 'Main Author'),
            new DateTimeImmutable('2025-01-01 12:00:00'),
            $comments
        );
    }

    private function createCommentInput(string $content = 'Comment content', ?Author $author = null): CommentInput
    {
        return new CommentInput(
            $content,
            $author ?? new Author('comment_author', 'Comment Author'),
            new DateTimeImmutable('2025-01-01 13:00:00'),
            []
        );
    }

    public function testPreprocessWithValidMentions(): void
    {
        $commentator = $this->createCommentator();
        $processor = new MentionProcessor();

        $comment = $this->createCommentInput('Comment', new Author('212', 'lila'));
        $thread = $this->createThreadInput('Thread', null, [$comment]);

        $content = '<p>Hello <a class="mention-account" data-mention="@lila" data-user="212">@lila</a>!</p>';
        
        $result = $processor->preprocess($commentator, $thread, $content, $thread);
        
        $this->assertSame($content, $result);
    }

    public function testPreprocessWithInvalidMentions(): void
    {
        $commentator = $this->createCommentator();
        $processor = new MentionProcessor();

        $thread = $this->createThreadInput('Thread');

        $content = '<p>Hello <a class="mention-account" data-mention="@nonexistent" data-user="999">@nonexistent</a>!</p>';
        
        $result = $processor->preprocess($commentator, $thread, $content, $thread);
        
        $this->assertNull($result);
    }

    public function testPreprocessWithMixedValidInvalidMentions(): void
    {
        $commentator = $this->createCommentator();
        $processor = new MentionProcessor();

        $comment = $this->createCommentInput('Comment', new Author('lilaframi', 'Lila Frami'));
        $thread = $this->createThreadInput('Thread', null, [$comment]);

        $content = '<p>Hello <a class="mention-account" data-mention="@Lila Frami" data-user="212">@Lila Frami</a> and <a class="mention-account" data-mention="@nonexistent" data-user="999">@nonexistent</a>!</p>';
        
        $result = $processor->preprocess($commentator, $thread, $content, $thread);
        
        $this->assertNull($result);
    }

    public function testPreprocessWithoutMentions(): void
    {
        $commentator = $this->createCommentator();
        $processor = new MentionProcessor();

        $thread = $this->createThreadInput('Thread');

        $content = '<p>Hello world!</p>';
        
        $result = $processor->preprocess($commentator, $thread, $content, $thread);
        
        $this->assertSame($content, $result);
    }

    public function testPostprocessReplacesValidMentions(): void
    {
        $commentator = $this->createCommentator();
        $processor = new MentionProcessor();

        $comment = $this->createCommentInput('Comment', new Author('lilaframi', 'Lila Frami'));
        $thread = $this->createThreadInput('Thread', null, [$comment]);

        $content = '<p>Hello <a class="mention-account" data-mention="@Lila Frami" data-user="212">@Lila Frami</a>!</p>';
        
        $result = $processor->postprocess($commentator, $thread, $content, $thread, $thread->author);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('data-mention="@Author 1"', $result);
        $this->assertStringContainsString('data-user="author1"', $result);
        $this->assertStringContainsString('>@Author 1<', $result);
    }

    public function testPostprocessWithMultipleMentions(): void
    {
        $commentator = $this->createCommentator();
        $processor = new MentionProcessor();

        $comment1 = $this->createCommentInput('Comment1', new Author('user1', 'User One'));
        $comment2 = $this->createCommentInput('Comment2', new Author('user2', 'User Two'));
        $thread = $this->createThreadInput('Thread', null, [$comment1, $comment2]);

        $content = '<p>Hello <a class="mention-account" data-mention="@User One" data-user="1">@User One</a> and <a class="mention-account" data-mention="@User Two" data-user="2">@User Two</a>!</p>';
        
        $result = $processor->postprocess($commentator, $thread, $content, $thread, $thread->author);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('data-mention="@Author 1"', $result);
        $this->assertStringContainsString('data-mention="@Author 2"', $result);
        $this->assertStringContainsString('>@Author 1<', $result);
        $this->assertStringContainsString('>@Author 2<', $result);
    }

    public function testPostprocessWithoutMentions(): void
    {
        $commentator = $this->createCommentator();
        $processor = new MentionProcessor();

        $thread = $this->createThreadInput('Thread');

        $content = '<p>Hello world!</p>';
        
        $result = $processor->postprocess($commentator, $thread, $content, $thread, $thread->author);
        
        $this->assertSame($content, $result);
    }

    public function testPreprocessWithDuplicateMentions(): void
    {
        $commentator = $this->createCommentator();
        $processor = new MentionProcessor();

        $comment = $this->createCommentInput('Comment', new Author('lilaframi', 'Lila Frami'));
        $thread = $this->createThreadInput('Thread', null, [$comment]);

        $content = '<p>Hello <a class="mention-account" data-mention="@Lila Frami" data-user="212">@Lila Frami</a> and again <a class="mention-account" data-mention="@Lila Frami" data-user="212">@Lila Frami</a>!</p>';
        
        $result = $processor->preprocess($commentator, $thread, $content, $thread);
        
        $this->assertSame($content, $result);
    }

    public function testPostprocessPreservesOtherAttributes(): void
    {
        $commentator = $this->createCommentator();
        $processor = new MentionProcessor();

        $comment = $this->createCommentInput('Comment', new Author('lilaframi', 'Lila Frami'));
        $thread = $this->createThreadInput('Thread', null, [$comment]);

        $content = '<p>Hello <a class="mention-account custom-class" data-mention="@Lila Frami" data-user="212" data-custom="value">@Lila Frami</a>!</p>';
        
        $result = $processor->postprocess($commentator, $thread, $content, $thread, $thread->author);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('class="mention-account custom-class"', $result);
        $this->assertStringContainsString('data-custom="value"', $result);
        $this->assertStringContainsString('data-mention="@Author 1"', $result);
        $this->assertStringContainsString('data-user="author1"', $result);
    }
}
