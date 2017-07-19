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
     * @dataProvider equalsProvider
     * @covers ::equals
     *
     * @param string $a
     * @param string $b
     * @param bool   $expected
     */
    public function testEquals(string $a, string $b, bool $expected)
    {
        $a = DateInterval::fromSpecification($a);
        $b = DateInterval::fromSpecification($b);
    
        self::assertSame($expected, $a->equals($b));
    }
    
    
    /**
     * @return array
     */
    public function equalsProvider() : array
    {
        return [
            ['P1Y1M1DT1H1M1S1U', 'P1Y1M1DT1H1M1S1U', true],
            ['P1Y1M1DT1H1M1S1U', 'P1Y1M1DT1H1M1S2U', false],
            ['P1Y1M1DT1H1M1S1U', 'P2Y1M1DT1H1M1S1U', false],
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


    /**
     * @dataProvider totalSecondsProvider
     * @covers ::totalSeconds
     *
     * @param string $specification
     * @param int    $expected
     */
    public function testTotalSeconds(string $specification, int $expected)
    {
        $interval = DateInterval::fromSpecification($specification);

        self::assertSame($expected, $interval->totalSeconds());
    }


    public function totalSecondsProvider() : array
    {
        return [
            ['PT1U', 0],
            ['PT1S1U', 1],
            ['PT1M1S1U', 61],
            ['PT1H1M1S1U', 3661],
        ];
    }


    /**
     * @dataProvider formatProvider
     * @covers ::format
     *
     * @param string $specification
     * @param string $format
     * @param string $expected
     */
    public function testFormat(string $specification, string $format, string $expected)
    {
        $interval = DateInterval::fromSpecification($specification);

        self::assertSame($expected, $interval->format($format));
    }


    public function formatProvider()
    {
        return [
            ['PT1U', '%y %m %d %h %i %s %u', '0 0 0 0 0 0 1'],
            ['PT1U', '%%y %%m %%d %%h %%i %%s %%u', '%y %m %d %h %i %s %u'], // Escaping, including microseconds.
            ['PT1H1M1S1U', '%y %m %d %h %i %s %u', '0 0 0 1 1 1 1'],
        ];
    }


    /**
     * @dataProvider containsProvider
     * @covers ::contains
     *
     * @param string $first
     * @param string $second
     * @param bool   $expected
     */
    public function testContains(string $first, string $second, bool $expected)
    {
        $first  = DateInterval::fromSpecification($first);
        $second = DateInterval::fromSpecification($second);

        self::assertSame($expected, $first->contains($second));
    }


    public function containsProvider()
    {
        return [
            ['PT1M', 'PT1S', true],
            ['PT1M', 'PT59S', true],
            ['PT1M', 'PT60S', true],
            ['PT1M', 'PT1M', true],
            ['PT1M', 'PT61S', false],
            ['PT1M', 'PT2M', false],
        ];
    }
}
