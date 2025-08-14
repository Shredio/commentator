<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\Clock\DatePoint;

final readonly class RandomDateTimeGenerator implements DateTimeGenerator
{

	public int $secondsToAdd;
	public DateTimeImmutable $targetDate;

	private DateTimeImmutable $maximum;

	public function __construct(
		DateTimeImmutable $minimum,
		DateTimeImmutable $maximum,
		int $randomLowerBound = 5 * 60,
		int $randomUpperBound = 10 * 60,
	)
	{
		if ($minimum > $maximum) {
			throw new InvalidArgumentException('Minimum date must be less than maximum date.');
		}
		if ($randomLowerBound < 0 || $randomUpperBound < 0) {
			throw new InvalidArgumentException('Random bounds must be non-negative.');
		}
		if ($randomLowerBound > $randomUpperBound) {
			throw new InvalidArgumentException('Random lower bound must be less than random upper bound.');
		}

		// move dates closer to the current time
		$this->secondsToAdd = max($this->getTime() - $maximum->getTimestamp() - mt_rand($randomLowerBound, $randomUpperBound), 0);
		$this->targetDate = $minimum->setTimestamp($minimum->getTimestamp() + $this->secondsToAdd);
		$this->maximum = $maximum->setTimestamp($maximum->getTimestamp() + $this->secondsToAdd);
	}

	public function getTargetDate(): DateTimeImmutable
	{
		return $this->targetDate;
	}

	public function getDate(DateTimeImmutable $referenceDate): DateTimeImmutable
	{
		$referenceDate = $referenceDate->setTimestamp($referenceDate->getTimestamp() + $this->secondsToAdd);

		if ($referenceDate < $this->targetDate) {
			throw new InvalidArgumentException('Reference date cannot be before target date.');
		}
		if ($referenceDate > $this->maximum) {
			throw new InvalidArgumentException('Reference date cannot be after maximum date.');
		}
		
		return $referenceDate;
	}

	private function getTime(): int
	{
		return (new DatePoint())->getTimestamp();
	}

}
