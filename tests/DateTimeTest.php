<?php

namespace Krixon\DateTime\Test;

use Krixon\DateTime\DateTime;

/**
 * @coversDefaultClass Krixon\DateTime\DateTime
 * @covers ::<protected>
 * @covers ::<private>
 */
class DateTimeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::fromFormat
     */
    public function testCreateFromFormat()
    {
        $string = '2015-01-01 15:10:22';
        $format = 'Y-m-d H:i:s';
        $date   = DateTime::fromFormat($format, $string, new \DateTimeZone('UTC'));
        
        $this->assertSame(1420125022, $date->timestamp());
    }
    
    
    /**
     * @covers ::create
     */
    public function testStaticCreation()
    {
        $now  = time();
        $date = DateTime::create('@' . $now);
        
        $this->assertSame($now, $date->timestamp());
    }
    
    
    /**
     * @covers ::now
     */
    public function testCreateForCurrentDateTime()
    {
        $now  = time();
        $date = DateTime::now(new \DateTimeZone('UTC'));
        
        $this->assertEquals($now, $date->timestamp(), '', 1); // Within 1 second should be accurate enough.
    }
    
    
    /**
     * @covers ::diff
     */
    public function testDiff()
    {
        $date1 = DateTime::create('2015-01-01T12:30:15Z');
        $date2 = DateTime::create('2016-04-29T17:44:16Z');
        
        $diff     = $date1->diff($date2);
        $expected = new \DateInterval('P1Y3M28DT3H14M1S');
        $format   = 'R|y|m|d|h|i|s';
        
        $this->assertSame($expected->format($format), $diff->format($format));
    }
    
    
    /**
     * @covers ::format
     */
    public function testFormat()
    {
        $date = DateTime::create('2015-01-01T12:30:15Z');
        
        $this->assertSame('2015-01-01 12:30:15', $date->format('Y-m-d H:i:s'));
    }
    
    
    /**
     * @covers ::offset
     */
    public function testOffset()
    {
        $date = DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Asia/Tokyo'));
        
        $this->assertSame(32400, $date->offset());
    }
    
    
    /**
     * @covers ::timezone
     */
    public function testTimezone()
    {
        $date = DateTime::now(new \DateTimeZone('Asia/Tokyo'));
        
        $this->assertInstanceOf(\DateTimeZone::class, $date->timezone());
        $this->assertSame('Asia/Tokyo', $date->timezone()->getName());
    }
    
    
    /**
     * @dataProvider equalityProvider
     * @covers ::equals
     *
     * @param DateTime $date1
     * @param DateTime $date2
     * @param bool     $expected
     */
    public function testEquality(DateTime $date1, DateTime $date2, bool $expected)
    {
        $this->assertSame($expected, $date1->equals($date2));
    }
    
    
    public function equalityProvider() : array
    {
        return [
            // Same date and timezone.
            [
                DateTime::create('2015-01-01T00:00:00'),
                DateTime::create('2015-01-01T00:00:00'),
                true,
            ],
            // Same date, different timezones.
            [
                DateTime::create('2015-01-01T00:00:00'),
                DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Europe/Paris')),
                false,
            ],
            // Different dates, same timezone.
            [
                DateTime::create('2015-01-01T00:00:00'),
                DateTime::create('2015-06-01T00:00:00'),
                false,
            ],
            // Equal date when the different timezones are taken into account.
            [
                DateTime::create('2014-12-31T15:00:00Z'),
                DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Asia/Tokyo')),
                true,
            ],
        ];
    }
    
    
    /**
     * @dataProvider isLaterThanProvider
     * @covers ::isLaterThan
     *
     * @param DateTime $date1
     * @param DateTime $date2
     * @param bool     $expected
     */
    public function testIsLaterThan(DateTime $date1, DateTime $date2, bool $expected)
    {
        $this->assertSame($expected, $date1->isLaterThan($date2));
    }
    
    
    public function isLaterThanProvider()
    {
        return [
            // Same date and timezone.
            [
                DateTime::create('2015-01-01T00:00:00'),
                DateTime::create('2015-01-01T00:00:00'),
                false,
            ],
            // Same date, different timezones. First is later than second.
            [
                DateTime::create('2015-01-01T00:00:00'),
                DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Europe/Paris')),
                true,
            ],
            // Same date, different timezones. Second is later than first.
            [
                DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Europe/Paris')),
                DateTime::create('2015-01-01T00:00:00'),
                false,
            ],
            // Same timezone. Second is later than first.
            [
                DateTime::create('2015-01-01T00:00:00'),
                DateTime::create('2015-06-01T00:00:00'),
                false,
            ],
            // Same timezone. First is later than second.
            [
                DateTime::create('2015-06-01T00:00:00'),
                DateTime::create('2015-01-01T00:00:00'),
                true,
            ],
            // Equal date when the different timezones are taken into account.
            [
                DateTime::create('2014-12-31T15:00:00Z'),
                DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Asia/Tokyo')),
                false,
            ],
            // First is later than second when the different timezones are taken into account.
            [
                DateTime::create('2014-12-31T15:00:01Z'),
                DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Asia/Tokyo')),
                true,
            ],
            // Second is later than first when the different timezones are taken into account.
            [
                DateTime::create('2014-12-31T15:00:00Z'),
                DateTime::create('2015-01-01T00:00:01', new \DateTimeZone('Asia/Tokyo')),
                false,
            ],
        ];
    }
    
    
    /**
     * @dataProvider isEarlierThanProvider
     * @covers ::isEarlierThan
     *
     * @param DateTime $date1
     * @param DateTime $date2
     * @param bool     $expected
     */
    public function testIsEarlierThan(DateTime $date1, DateTime $date2, bool $expected)
    {
        $this->assertSame($expected, $date1->isEarlierThan($date2));
    }
    
    
    public function isEarlierThanProvider() : array
    {
        return [
            // Same date and timezone.
            [
                DateTime::create('2015-01-01T00:00:00'),
                DateTime::create('2015-01-01T00:00:00'),
                false,
            ],
            // Same date, different timezones. First is later than second.
            [
                DateTime::create('2015-01-01T00:00:00'),
                DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Europe/Paris')),
                false,
            ],
            // Same date, different timezones. Second is later than first.
            [
                DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Europe/Paris')),
                DateTime::create('2015-01-01T00:00:00'),
                true,
            ],
            // Same timezone. Second is later than first.
            [
                DateTime::create('2015-01-01T00:00:00'),
                DateTime::create('2015-06-01T00:00:00'),
                true,
            ],
            // Same timezone. First is later than second.
            [
                DateTime::create('2015-06-01T00:00:00'),
                DateTime::create('2015-01-01T00:00:00'),
                false,
            ],
            // Equal date when the different timezones are taken into account.
            [
                DateTime::create('2014-12-31T15:00:00Z'),
                DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Asia/Tokyo')),
                false,
            ],
            // First is later than second when the different timezones are taken into account.
            [
                DateTime::create('2014-12-31T15:00:01Z'),
                DateTime::create('2015-01-01T00:00:00', new \DateTimeZone('Asia/Tokyo')),
                false,
            ],
            // Second is later than first when the different timezones are taken into account.
            [
                DateTime::create('2014-12-31T15:00:00Z'),
                DateTime::create('2015-01-01T00:00:01', new \DateTimeZone('Asia/Tokyo')),
                true,
            ],
        ];
    }
    
    
    /**
     * @covers ::withTimeAtMidnight
     */
    public function testWithTimeAtMidnight()
    {
        $date = DateTime::create('2015-09-15T13:46:21Z')->withTimeAtMidnight();
        
        self::assertSame('2015-09-15T00:00:00+00:00', $date->format('c'));
    }
    
    
    /**
     * @covers ::withDateAtStartOfYear
     */
    public function testWithDateAtStartOfYear()
    {
        $date = DateTime::create('2015-09-15T13:46:21Z')->withDateAtStartOfYear();
        
        self::assertSame('2015-01-01T00:00:00+00:00', $date->format('c'));
    }
    
    
    /**
     * @covers ::withDateAtStartOfWeek
     */
    public function testWithDateAtStartOfWeek()
    {
        $date = DateTime::create('2015-09-15T13:46:21Z')->withDateAtStartOfWeek("en_GB");
        
        self::assertSame('2015-09-14T00:00:00+00:00', $date->format('c'));
    }
    
    
    /**
     * @covers ::__clone
     */
    public function testClonesDoNotShareSameWrappedDateTime()
    {
        $a = DateTime::create('2016-01-01T00:00:00Z');
        $b = clone $a;
        $b = $b->add('P1Y');
        
        self::assertSame('2017-01-01T00:00:00+00:00', $b->format('c'));
        self::assertSame('2016-01-01T00:00:00+00:00', $a->format('c'), 'Instance has been mutated.');
    }
}
