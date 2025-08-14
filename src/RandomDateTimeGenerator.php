<?php declare(strict_types = 1);

namespace Shredio\Commentator;

use DateTimeImmutable;
use DateTimeZone;

final readonly class RandomDateTimeGenerator implements DateTimeGenerator
{
    public function generateTargetDate(): DateTimeImmutable
    {
        $timestampTo = time();
        $timestampFrom = $timestampTo - 60 * 60 * 6; // 6 hours ago

        $randomTimestamp = mt_rand($timestampFrom, $timestampTo);

        $dateTime = new DateTimeImmutable('@' . $randomTimestamp);
        $timeZone = date_default_timezone_get();
        if ($timeZone !== 'UTC') {
            $dateTime = $dateTime->setTimezone(new DateTimeZone($timeZone));
        }

        return $dateTime;
    }
}