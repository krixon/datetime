<?php

namespace Krixon\DateTime\Test;

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
        
        $this->assertTrue($range1->equals($range2));
    }
    
    
    /**
     * @covers ::containsNow
     */
    public function testContainsNow()
    {
        $from  = DateTime::now()->subtract('PT1M');
        $until = DateTime::now()->add('PT1M');
        
        $range = new DateRange($from, $until);
        
        $this->assertTrue($range->containsNow());
    }
}
