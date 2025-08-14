<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Shredio\Commentator\Input\ThreadInput;
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
		if ($minimum->getTimezone()->getName() !== 'UTC') {
			throw new InvalidArgumentException('Minimum date must be in UTC timezone.');
		}
		if ($maximum->getTimezone()->getName() !== 'UTC') {
			throw new InvalidArgumentException('Maximum date must be in UTC timezone.');
		}
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
		if ($referenceDate < $this->targetDate) {
			throw new InvalidArgumentException('Reference date cannot be before target date.');
		}
		
		if ($referenceDate > $this->maximum) {
			throw new InvalidArgumentException('Reference date cannot be after maximum date.');
		}
		
		return $referenceDate->setTimestamp($referenceDate->getTimestamp() + $this->secondsToAdd);
	}

	private function getTime(): int
	{
		return (new DatePoint(timezone: new DateTimeZone('UTC')))->getTimestamp();
	}

}
