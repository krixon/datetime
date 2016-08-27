<?php

namespace Krixon\DateTime\Test;

use Krixon\DateTime\DateInterval;
use Krixon\DateTime\DateTime;

/**
 * @coversDefaultClass Krixon\DateTime\DateInterval
 * @covers ::<protected>
 * @covers ::<private>
 */
class DateIntervalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider createFromSpecificationProvider
     * @covers ::fromSpecification
     *
     * @param string $specification
     * @param string $expected
     */
    public function testCreateFromSpecification(string $specification, string $expected)
    {
        $interval = DateInterval::fromSpecification($specification);
        
        self::assertSame(
            $expected,
            $interval->format('%yy %mm %dd %hh %im %ss %uµs')
        );
    }
    
    
    /**
     * @return array
     */
    public function createFromSpecificationProvider() : array
    {
        return [
            ['P1Y1M1DT1H1M1S1U', '1y 1m 1d 1h 1m 1s 1µs'],
            ['P42Y1DT1H123456U', '42y 0m 1d 1h 0m 0s 123456µs'],
            ['PT24H61M123456U', '0y 0m 0d 24h 61m 0s 123456µs'],
            ['P42U', '0y 0m 0d 0h 0m 0s 42µs'],
        ];
    }
    
    
    /**
     * @dataProvider diffProvider
     * @covers ::diff
     *
     * @param string $a
     * @param string $b
     * @param string $expected
     * @param bool   $abs
     */
    public function testDiff(string $a, string $b, string $expected, bool $abs = false)
    {
        $a    = DateTime::create($a);
        $b    = DateTime::create($b);
        $diff = DateInterval::diff($a, $b, $abs);
    
        self::assertSame(
            $expected,
            $diff->format('%yy %mm %dd %hh %im %ss %uµs %atd')
        );
    }
    
    
    public function diffProvider() : array
    {
        return [
            ['2015-01-01T00:00:00.000000', '2016-02-03T04:05:06.123456', '1y 1m 2d 4h 5m 6s 123456µs 398td'],
        ];
    }
}
