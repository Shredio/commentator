<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use DOMElement;
use DOMNode;
use LogicException;
use Masterminds\HTML5;
use Shredio\Commentator\Input\PostInput;
use Shredio\Commentator\Input\ThreadInput;
use Shredio\Commentator\ValueObject\Author;

final readonly class MentionProcessor implements CommentatorContentProcessor
{

	private HTML5 $htmlParser;

	public function __construct() {
		$this->htmlParser = new HTML5([
			'disable_html_ns' => true,
		]);
    }

	public function preprocess(
		Commentator $commentator,
		ThreadInput $thread,
		string $content,
		PostInput $input,
	): ?string
	{
        if (!$this->hasMentions($content)) {
            return $content;
        }

        $elements = $this->extractMentionElements($content);
        if ($elements === []) {
            return $content;
        }

        $authorIndex = $thread->getAuthorIndexByNicknames();
        foreach ($this->getNicknamesFromElements($elements) as $nickname) {
            if (!isset($authorIndex[$nickname])) {
                return null;
            }
        }

        return $content;
    }

	public function postprocess(
		Commentator $commentator,
		ThreadInput $thread,
		string $content,
		PostInput $input,
		Author $author,
	): string
	{
        if (!$this->hasMentions($content)) {
            return $content;
        }

		$fragment = $this->htmlParser->loadHTMLFragment($content);
		$elements = $this->getMentionElements($fragment);
		if ($elements === []) {
			return $content;
		}

		$dom = $fragment->ownerDocument;
		if ($dom === null) {
			throw new LogicException('The document is not set in the fragment.');
		}

		foreach ($elements as $element) {
			$originalNickname = $element->getAttribute('data-mention');
			$originalId = $element->getAttribute('data-user');

			$author = $commentator->getPostAuthor(new Author($originalId, $originalNickname));
			$element->setAttribute('data-mention', '@' . $author->nickname);
			$element->setAttribute('data-user', $author->id);
			$element->textContent = '@' . $author->nickname;
		}

		$html = $dom->saveHTML($fragment);
		if ($html === false) {
			throw new LogicException('Cannot convert HTML fragment to string.');
		}

        return $html;
    }

    private function hasMentions(string $content): bool
    {
        return str_contains($content, 'data-mention=');
    }

    /**
     * @return list<DOMElement>
     */
    private function extractMentionElements(string $content): array
    {
		return $this->getMentionElements($this->htmlParser->loadHTMLFragment($content));
    }

	/**
	 * @param DOMNode $node
	 * @return list<DOMElement>
	 */
	private function getMentionElements(DOMNode $node): array
	{
		$mentions = [];
		if ($node instanceof DOMElement && $node->hasAttribute('data-mention')) {
			$mentions[] = $node;
		}

		foreach ($node->childNodes as $child) {
			$mentions = array_merge($mentions, $this->getMentionElements($child));
		}

		return $mentions;
	}

	/**
	 * @param list<DOMElement> $elements
	 * @return list<string>
	 */
	private function getNicknamesFromElements(array $elements): array
	{
		$nicknames = [];
		foreach ($elements as $element) {
			if ($element->hasAttribute('data-mention')) {
				$value = ltrim($element->getAttribute('data-mention'), '@');
				$nicknames[$value] = $value;
			}
		}

		return array_values($nicknames);
	}

}
