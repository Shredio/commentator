<?php declare(strict_types = 1);

namespace Tests\Unit\Flow;

use DateTimeImmutable;
use Shredio\Commentator\Enum\Gender;
use Shredio\Commentator\Flow\ThreadFlow;
use Shredio\Commentator\Flow\ThreadFlowItem;
use Tests\TestCase;

final class ThreadFlowInstanceTest extends TestCase
{

	public function testConstructorWithMainPostOnly(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post content',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$flow = new ThreadFlow($mainPost);
		
		$result = $flow->toString();
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/main_post_only.txt', $result);
	}

	public function testConstructorWithChildPosts(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post content',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$childPost1 = new ThreadFlowItem(
			'Child post 1',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$childPost2 = new ThreadFlowItem(
			'Child post 2',
			'Jana',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);

		$flow = new ThreadFlow($mainPost, [$childPost1, $childPost2]);

		$result = $flow->toString();

		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/with_child_posts.txt', $result);
	}

	public function testConstructorWithSameLevelPosts(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post content',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$sameLevelPost1 = new ThreadFlowItem(
			'Same level 1',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$sameLevelPost2 = new ThreadFlowItem(
			'Same level 2',
			'Jana',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		
		$flow = new ThreadFlow($mainPost, [], [$sameLevelPost1, $sameLevelPost2]);
		
		$result = $flow->toString();
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/with_same_level_posts.txt', $result);
	}

	public function testConstructorWithAllParameters(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post content',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$childPost1 = new ThreadFlowItem(
			'Child post 1',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$childPost2 = new ThreadFlowItem(
			'Child post 2',
			'Karel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$sameLevelPost1 = new ThreadFlowItem(
			'Same level 1',
			'Jana',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		$sameLevelPost2 = new ThreadFlowItem(
			'Same level 2',
			'Alena',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		
		$flow = new ThreadFlow($mainPost, [$childPost1, $childPost2], [$sameLevelPost1, $sameLevelPost2]);
		
		$result = $flow->toString();
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/with_all_parameters.txt', $result);
	}

	public function testWithNestedPost(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post content',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nestedPost = new ThreadFlowItem(
			'Nested post content',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		
		$flow = new ThreadFlow($mainPost);
		$newFlow = $flow->withNestedPost($nestedPost);
		
		$result = $newFlow->toString();
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/with_nested_post.txt', $result);
	}

	public function testWithMultipleNestedPosts(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post content',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nestedPost1 = new ThreadFlowItem(
			'First nested post',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nestedPost2 = new ThreadFlowItem(
			'Second nested post',
			'Jana',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		
		$flow = new ThreadFlow($mainPost);
		$newFlow = $flow
			->withNestedPost($nestedPost1)
			->withNestedPost($nestedPost2);
		
		$result = $newFlow->toString();
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/with_multiple_nested_posts.txt', $result);
	}

	public function testWithNextPost(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post content',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$childPost = new ThreadFlowItem(
			'Child post',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nextPost = new ThreadFlowItem(
			'Next post content',
			'Jana',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		
		$flow = new ThreadFlow($mainPost, [$childPost]);
		$newFlow = $flow->withNextPost($nextPost);
		
		$result = $newFlow->toString();
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/with_next_post.txt', $result);
	}

	public function testWithMultipleNextPosts(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post content',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$childPost = new ThreadFlowItem(
			'Child post',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nextPost1 = new ThreadFlowItem(
			'First next post',
			'Jana',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		$nextPost2 = new ThreadFlowItem(
			'Second next post',
			'Karel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		
		$flow = new ThreadFlow($mainPost, [$childPost]);
		$newFlow = $flow
			->withNextPost($nextPost1)
			->withNextPost($nextPost2);
		
		$result = $newFlow->toString();
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/with_multiple_next_posts.txt', $result);
	}

	public function testImmutability(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post content',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nestedPost = new ThreadFlowItem(
			'Nested post',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nextPost = new ThreadFlowItem(
			'Next post',
			'Jana',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		
		$originalFlow = new ThreadFlow($mainPost);
		$newFlow1 = $originalFlow->withNestedPost($nestedPost);
		$newFlow2 = $originalFlow->withNextPost($nextPost);
		
		$originalResult = $originalFlow->toString();
		$nestedResult = $newFlow1->toString();
		$nextResult = $newFlow2->toString();
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/immutable_original.txt', $originalResult);
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/immutable_nested.txt', $nestedResult);
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/immutable_next.txt', $nextResult);
	}

	public function testComplexCombination(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post content',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nestedPost1 = new ThreadFlowItem(
			'First nested',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nestedPost2 = new ThreadFlowItem(
			'Second nested',
			'Jana',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		$sameLevelPost1 = new ThreadFlowItem(
			'First same level',
			'Karel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$sameLevelPost2 = new ThreadFlowItem(
			'Second same level',
			'Alena',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		
		$flow = (new ThreadFlow($mainPost))
			->withNestedPost($nestedPost1)
			->withNestedPost($nestedPost2)
			->withNextPost($sameLevelPost1)
			->withNextPost($sameLevelPost2);
		
		$result = $flow->toString();
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/complex_combination.txt', $result);
	}

	public function testHtmlContentNormalization(): void
	{
		$mainPost = new ThreadFlowItem(
			'<p>Main <strong>post</strong> content</p>',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nestedPost = new ThreadFlowItem(
			'<p>Nested <em>post</em></p><p>Second paragraph</p>',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$nextPost = new ThreadFlowItem(
			'<a href="#">Link post</a>',
			'Jana',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		
		$flow = (new ThreadFlow($mainPost))
			->withNestedPost($nestedPost)
			->withNextPost($nextPost);
		
		$result = $flow->toString();

		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/html_normalization.txt', $result);
	}

	public function testDeepNesting(): void
	{
		$mainPost = new ThreadFlowItem(
			'Main post',
			'Marcel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$level1 = new ThreadFlowItem(
			'Level 1',
			'Pepa',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$level2 = new ThreadFlowItem(
			'Level 2',
			'Jana',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		$level3 = new ThreadFlowItem(
			'Level 3',
			'Karel',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$level4 = new ThreadFlowItem(
			'Level 4',
			'Alena',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		$sameLevel1 = new ThreadFlowItem(
			'Same level 1',
			'Petr',
			Gender::Male,
			new DateTimeImmutable('2025-08-13')
		);
		$sameLevel2 = new ThreadFlowItem(
			'Same level 2',
			'Marie',
			Gender::Female,
			new DateTimeImmutable('2025-08-13')
		);
		
		$flow = (new ThreadFlow($mainPost))
			->withNestedPost($level1)
			->withNestedPost($level2)
			->withNestedPost($level3)
			->withNestedPost($level4)
			->withNextPost($sameLevel1)
			->withNextPost($sameLevel2);
		
		$result = $flow->toString();
		
		$this->assertStringEqualsFile(__DIR__ . '/expected/instance/deep_nesting.txt', $result);
	}

}
