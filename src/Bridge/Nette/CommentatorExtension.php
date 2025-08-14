<?php declare(strict_types = 1);

namespace Shredio\Commentator\Bridge\Nette;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Shredio\Commentator\CommentatorFactory;
use Shredio\Commentator\CommentatorLocaleAwareFactory;
use stdClass;

final class CommentatorExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'authors' => Expect::arrayOf(Expect::listOf(Expect::structure([
				'id' => Expect::mixed()->required(),
				'nickname' => Expect::string()->required(),
				'gender' => Expect::anyOf('female', 'male')->default('male')
			])->castTo('array')), Expect::string())->required(),
			'instructionFile' => Expect::string()->required(),
			'model' => Expect::string()->required(),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $config */
		$config = $this->getConfig();

		$builder->addDefinition($this->prefix('commentatorFactory'))
			->setType(CommentatorFactory::class)
			->setFactory(CommentatorLocaleAwareFactory::class, [
				'model' => $config->model,
				'instructionFile' => $config->instructionFile,
				'authors' => $this->dumpAuthors($config->authors),
			]);
	}

	/**
	 * @param array<string, list<array{ id: string|int, nickname: string, gender: 'male'|'female' }>> $authors
	 * @return array<string, list<Statement>>
	 */
	private function dumpAuthors(array $authors): array
	{
		$return = [];

		foreach (CommentatorLocaleAwareFactory::processAuthors($authors) as $locale => $authorList) {
			foreach ($authorList as $author) {
				$return[$locale][] = new Statement($author::class, get_object_vars($author));
 			}
		}

		return $return;
	}

}
