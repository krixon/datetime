<?php

namespace Krixon\DateTime\Test;

use Krixon\DateTime\DateTime;
use PHPUnit\Framework\TestCase;

class DateTimeTestCase extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        parent::setUpBeforeClass();

        date_default_timezone_set('UTC');
    }


    protected static function assertSameDate(string $expected, DateTime $date)
    {
        $expected = DateTime::create($expected);
        
        self::assertTrue(
            $date->equals($expected),
            sprintf("Failed asserting that $date equals expected $expected.")
        );
    }
}
