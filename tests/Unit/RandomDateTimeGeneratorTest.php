<?php declare(strict_types = 1);

namespace Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shredio\Commentator\RandomDateTimeGenerator;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class RandomDateTimeGeneratorTest extends TestCase
{
    use ClockSensitiveTrait;

    public function testConstructorValidatesTimezones(): void
    {
        $minimum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('Europe/Prague'));
        $maximum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('UTC'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum date must be in UTC timezone.');

        new RandomDateTimeGenerator($minimum, $maximum);
    }

    public function testConstructorValidatesMaximumTimezone(): void
    {
        $minimum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('UTC'));
        $maximum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('Europe/Prague'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum date must be in UTC timezone.');

        new RandomDateTimeGenerator($minimum, $maximum);
    }

    public function testConstructorValidatesDateOrder(): void
    {
        $minimum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('UTC'));
        $maximum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('UTC'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum date must be less than maximum date.');

        new RandomDateTimeGenerator($minimum, $maximum);
    }

    public function testConstructorValidatesRandomBoundsNonNegative(): void
    {
        $minimum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('UTC'));
        $maximum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('UTC'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Random bounds must be non-negative.');

        new RandomDateTimeGenerator($minimum, $maximum, -1, 10);
    }

    public function testConstructorValidatesRandomBoundsOrder(): void
    {
        $minimum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('UTC'));
        $maximum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('UTC'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Random lower bound must be less than random upper bound.');

        new RandomDateTimeGenerator($minimum, $maximum, 10, 5);
    }

    public function testGetTargetDateWithPredictableValues(): void
    {
        self::mockTime('2024-01-02T13:00:00+00:00');

        $minimum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('UTC'));
        $maximum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('UTC'));
        
        $generator = new RandomDateTimeGenerator($minimum, $maximum, 0, 0);
        
        $targetDate = $generator->getTargetDate();
        
        $this->assertEquals('2024-01-01T01:00:00+00:00', $targetDate->format('c'));
    }

    public function testGetDateWithPredictableValues(): void
    {
        self::mockTime('2024-01-02T13:00:00+00:00');

        $minimum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('UTC'));
        $maximum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('UTC'));
        
        $generator = new RandomDateTimeGenerator($minimum, $maximum, 0, 0);
        
        $referenceDate = new DateTimeImmutable('2024-01-01T10:00:00', new DateTimeZone('UTC'));
        $resultDate = $generator->getDate($referenceDate);
        
        $this->assertEquals('2024-01-01T11:00:00+00:00', $resultDate->format('c'));
    }

    public function testGeneratorWithZeroSecondsToAdd(): void
    {
        self::mockTime('2024-01-02T11:00:00+00:00');

        $minimum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('UTC'));
        $maximum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('UTC'));
        
        $generator = new RandomDateTimeGenerator($minimum, $maximum, 0, 0);
        
        $targetDate = $generator->getTargetDate();
        $this->assertEquals('2024-01-01T00:00:00+00:00', $targetDate->format('c'));
        
        $referenceDate = new DateTimeImmutable('2024-01-01T10:00:00', new DateTimeZone('UTC'));
        $resultDate = $generator->getDate($referenceDate);
        $this->assertEquals('2024-01-01T10:00:00+00:00', $resultDate->format('c'));
    }

    public function testConstructorWithValidParameters(): void
    {
        $minimum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('UTC'));
        $maximum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('UTC'));
        
        $generator = new RandomDateTimeGenerator($minimum, $maximum, 0, 0);
        
        $this->assertInstanceOf(RandomDateTimeGenerator::class, $generator);
        $this->assertIsInt($generator->secondsToAdd);
        $this->assertInstanceOf(DateTimeImmutable::class, $generator->targetDate);
    }

    public function testGetDateValidatesReferenceDateNotBeforeTargetDate(): void
    {
        self::mockTime('2024-01-02T13:00:00+00:00');

        $minimum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('UTC'));
        $maximum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('UTC'));
        
        $generator = new RandomDateTimeGenerator($minimum, $maximum, 0, 0);
        
        $referenceDateBeforeTarget = new DateTimeImmutable('2024-01-01T00:30:00', new DateTimeZone('UTC'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reference date cannot be before target date.');

        $generator->getDate($referenceDateBeforeTarget);
    }

    public function testGetDateValidatesReferenceDateNotAfterMaximum(): void
    {
        self::mockTime('2024-01-02T13:00:00+00:00');

        $minimum = new DateTimeImmutable('2024-01-01T00:00:00', new DateTimeZone('UTC'));
        $maximum = new DateTimeImmutable('2024-01-02T12:00:00', new DateTimeZone('UTC'));
        
        $generator = new RandomDateTimeGenerator($minimum, $maximum, 0, 0);
        
        $referenceDateAfterMaximum = new DateTimeImmutable('2024-01-02T13:30:00', new DateTimeZone('UTC'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reference date cannot be after maximum date.');

        $generator->getDate($referenceDateAfterMaximum);
    }
}
