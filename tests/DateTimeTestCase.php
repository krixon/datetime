<?php

namespace Krixon\DateTime\Test;

use Krixon\DateTime\DateTime;

class DateTimeTestCase extends \PHPUnit_Framework_TestCase
{
    protected static function assertSameDate(string $expected, DateTime $date)
    {
        $expected = DateTime::create($expected);
        
        self::assertTrue(
            $date->equals($expected),
            sprintf("Failed asserting that $date equals expected $expected.")
        );
    }
}
