<?php declare(strict_types = 1);

namespace Shredio\Commentator;

interface CommentatorFactory
{

	public function create(string $locale): Commentator;

	/**
	 * @return list<string>
	 */
	public function getAvailableLocales(): array;

}
