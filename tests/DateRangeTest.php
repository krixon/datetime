<?php

namespace Krixon\DateTime\Test;

use Krixon\DateTime\DateInterval;
use Krixon\DateTime\DateRange;
use Krixon\DateTime\DateTime;

/**
 * @coversDefaultClass Krixon\DateTime\DateRange
 * @covers ::<protected>
 * @covers ::<private>
 */
class DateRangeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::from
     */
    public function testFrom()
    {
        $from  = DateTime::create('2015-01-01T00:00:00Z');
        $until = DateTime::create('2016-01-01T00:00:00Z');
        $range = new DateRange($from, $until);
        
        self::assertTrue($from->equals($range->from()));
    }
    
    
    /**
     * @covers ::until
     */
    public function testUntil()
    {
        $from  = DateTime::create('2015-01-01T00:00:00Z');
        $until = DateTime::create('2016-01-01T00:00:00Z');
        $range = new DateRange($from, $until);
        
        self::assertTrue($until->equals($range->until()));
    }
    
    
    /**
     * @covers ::equals
     */
    public function testEquals()
    {
        $from  = DateTime::create('2015-01-01T00:00:00Z');
        $until = DateTime::create('2016-01-01T00:00:00Z');
        $a     = new DateRange($from, $until);
        $b     = new DateRange($from, $until);
        $c     = new DateRange($from->subtract('PT1H'), $until);
        
        self::assertTrue($a->equals($a));
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
    
    
    /**
     * @covers ::diff
     */
    public function testDiff()
    {
        $from  = DateTime::create('2015-01-01T00:00:00.000000');
        $until = DateTime::create('2016-02-03T04:05:06.123456');
        $range = new DateRange($from, $until);
        
        $rangeDiff  = $range->diff();
        $manualDiff = DateInterval::diff($from, $until);
        
        self::assertTrue($manualDiff->equals($rangeDiff));
    }
    
    
    /**
     * @dataProvider containsDateTimeProvider
     * @covers ::contains
     *
     * @param DateTime $from
     * @param DateTime $until
     * @param DateTime $test
     * @param bool     $shouldContainDate
     */
    public function testContainsDateTime(DateTime $from, DateTime $until, DateTime $test, $shouldContainDate)
    {
        $range = new DateRange($from, $until);
        
        $this->assertSame($shouldContainDate, $range->contains($test));
    }
    
    
    /**
     * @return array
     */
    public function containsDateTimeProvider() : array
    {
        $from2015  = DateTime::create('2015-01-01T00:00:00Z');
        $until2016 = DateTime::create('2016-01-01T00:00:00Z');
        
        $paris = new \DateTimeZone('Europe/Paris');
        $tokyo = new \DateTimeZone('Asia/Tokyo');
        
        return [
            
            // Same timezone for both dates in the range and the test date.
            
            [$from2015, $until2016, DateTime::create('2015-01-01T00:00:00Z'), true],
            [$from2015, $until2016, DateTime::create('2015-01-02T00:00:00Z'), true],
            [$from2015, $until2016, DateTime::create('2015-06-15T12:14:09Z'), true],
            [$from2015, $until2016, DateTime::create('2015-12-31T00:00:00Z'), true],
            [$from2015, $until2016, DateTime::create('2015-12-31T23:59:59Z'), true],
            [$from2015, $until2016, DateTime::create('2016-01-01T00:00:00Z'), false],
            [$from2015, $until2016, DateTime::create('2014-12-31T23:59:59Z'), false],
            
            // Date range dates are both UTC, test date varies.
            
            [$from2015, $until2016, DateTime::create('2015-01-01T00:00:00', $paris), false], // 2014-12-31T23:00:00Z
            [$from2015, $until2016, DateTime::create('2015-01-01T01:00:00', $paris), true],  // 2015-01-01T00:00:00Z
            [$from2015, $until2016, DateTime::create('2015-01-01T00:00:00', $tokyo), false], // 2014-12-31T15:00:00Z
            [$from2015, $until2016, DateTime::create('2015-01-01T09:00:00', $tokyo), true],  // 2015-01-01T00:00:00Z
            
            // TODO: Different timezones for both dates in the range as well as the test date.
        ];
    }
    
    
    /**
     * @covers ::__construct
     */
    public function testCreatesCorrectRangeRegardlessOfArgumentOrder()
    {
        $from  = DateTime::create('2016-01-01T00:00:00Z');
        $until = DateTime::create('2015-01-01T00:00:00Z');
        
        $range1 = new DateRange($from, $until);
        $range2 = new DateRange($until, $from);
    
        self::assertTrue($range1->equals($range2));
    }
    
    
    /**
     * @covers ::containsNow
     */
    public function testContainsNow()
    {
        $from  = DateTime::now()->subtract('PT1M');
        $until = DateTime::now()->add('PT1M');
        
        $range = new DateRange($from, $until);
    
        self::assertTrue($range->containsNow());
    }
    
    
    /**
     * @dataProvider totalDaysProvider
     * @covers ::totalDays
     *
     * @param string $from
     * @param string $until
     * @param int    $expected
     */
    public function testTotalDays(string $from, string $until, int $expected)
    {
        $from  = DateTime::create($from);
        $until = DateTime::create($until);
        
        $range = new DateRange($from, $until);
        
        self::assertSame($expected, $range->totalDays());
    }
    
    
    public function totalDaysProvider() : array
    {
        return [
            ['2015-01-01T00:00:00Z', '2016-01-01T00:00:00Z', 365],
            ['2016-01-01T00:00:00Z', '2017-01-01T00:00:00Z', 366],
            ['2015-02-01T00:00:00Z', '2015-03-01T00:00:00Z', 28],
            ['2016-02-01T00:00:00Z', '2016-03-01T00:00:00Z', 29],
        ];
    }
    
    
    /**
     * @dataProvider totalWeeksProvider
     * @covers ::totalWeeks
     *
     * @param string $from
     * @param string $until
     * @param int    $expected
     */
    public function testTotalWeeks(string $from, string $until, int $expected)
    {
        $from  = DateTime::create($from);
        $until = DateTime::create($until);
        
        $range = new DateRange($from, $until);
        
        self::assertSame($expected, $range->totalWeeks());
    }
    
    
    public function totalWeeksProvider() : array
    {
        return [
            ['2015-01-01T00:00:00Z', '2016-01-01T00:00:00Z', 52],
            ['2016-01-01T00:00:00Z', '2017-01-01T00:00:00Z', 52],
            ['2015-02-01T00:00:00Z', '2015-03-01T00:00:00Z', 4],
            ['2015-02-01T00:00:00Z', '2015-03-07T00:00:00Z', 4],
            ['2016-02-01T00:00:00Z', '2016-03-07T00:00:00Z', 5],
        ];
    }
    
    
    /**
     * @dataProvider totalMonthsProvider
     * @covers ::totalMonths
     *
     * @param string $from
     * @param string $until
     * @param int    $expected
     */
    public function testTotalMonths(string $from, string $until, int $expected)
    {
        $from  = DateTime::create($from);
        $until = DateTime::create($until);
        
        $range = new DateRange($from, $until);
        
        self::assertSame($expected, $range->totalMonths());
    }
    
    
    public function totalMonthsProvider() : array
    {
        return [
            ['2015-01-01T00:00:00Z', '2016-01-01T00:00:00Z', 12],
            ['2016-01-01T00:00:00Z', '2017-01-01T00:00:00Z', 12],
            ['2015-02-01T00:00:00Z', '2015-03-01T00:00:00Z', 1],
            ['2016-02-01T00:00:00Z', '2016-03-01T00:00:00Z', 1],
            ['2010-01-01T00:00:00Z', '2014-01-01T00:00:00Z', 48],
        ];
    }
    
    
    /**
     * @dataProvider totalYearsProvider
     * @covers ::totalYears
     *
     * @param string $from
     * @param string $until
     * @param int    $expected
     */
    public function testTotalYears(string $from, string $until, int $expected)
    {
        $from  = DateTime::create($from);
        $until = DateTime::create($until);
        
        $range = new DateRange($from, $until);
        
        self::assertSame($expected, $range->totalYears());
    }
    
    
    public function totalYearsProvider() : array
    {
        return [
            ['2015-01-01T00:00:00Z', '2016-01-01T00:00:00Z', 1],
            ['2016-01-01T00:00:00Z', '2017-01-01T00:00:00Z', 1],
            ['2015-02-01T00:00:00Z', '2015-03-01T00:00:00Z', 0],
            ['2010-01-01T00:00:00Z', '2014-01-01T00:00:00Z', 4],
        ];
    }
}
