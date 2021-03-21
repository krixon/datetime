<?php

namespace Krixon\DateTime\Test;

use Krixon\DateTime\DateTime;

/**
 * @coversDefaultClass \Krixon\DateTime\DateTime
 * @covers ::<protected>
 * @covers ::<private>
 */
class DateTimeTest extends DateTimeTestCase
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
     * @covers ::now
     */
    public function testCreateForCurrentDateTimeUsesMicrosecondResolution()
    {
        // Because "now" might happen to have been at 000000µs, we take two instances with a small 10µs sleep between
        // them. This is still not foolproof because the time to construct $a might have taken precisely 999990µs,
        // so we run the test up to 10 times and pass if any result provides the expected resolution. There is still
        // the possibility of a false negative here, but it's pretty unlikely.
        
        
        for ($i = 0; $i < 10; $i++) {
            $a = DateTime::now(new \DateTimeZone('UTC'));
            usleep(10);
            $b = DateTime::now(new \DateTimeZone('UTC'));
            
            foreach ([$a, $b] as $date) {
                if ($date->microsecond() > 0) {
                    self::assertTrue(true);
                    return;
                }
            }
        }
        
        self::fail('Microsecond resolution was not captured.');
    }
    
    
    /**
     * @covers ::now
     */
    public function testCreateForCurrentDateTimeAppliesTimezoneCorrectly()
    {
        $utc   = DateTime::now(new \DateTimeZone('UTC'));
        $tokyo = DateTime::now(new \DateTimeZone('Asia/Tokyo'));
        
        // Timestamp should be the same.
        // Within 1 second should be accurate enough.
        $this->assertEquals($utc->timestamp(), $tokyo->timestamp(), '', 1);
        
        // String representations should be different.
        // Go down to minute resolution as seconds and µs are too risky to test.
        $format = 'Y-m-d H:i';
        self::assertNotSame($utc->format($format), $tokyo->format($format));
    }
    
    
    /**
     * @covers ::fromTimestampWithMilliseconds
     */
    public function testCreateFromTimestampWithMilliseconds()
    {
        $timestamp = 1471690033123;
        $date      = DateTime::fromTimestampWithMilliseconds($timestamp);
        
        self::assertSame('2016-08-20 10:47:13.123000', $date->format('Y-m-d H:i:s.u'));
    }


    /**
     * @param int    $timestamp
     * @param string $expected
     *
     * @dataProvider createFromTimestampWithMicrosecondsProvider
     * @covers ::fromTimestampWithMicroseconds
     */
    public function testCreateFromTimestampWithMicroseconds(int $timestamp, string $expected)
    {
        $date = DateTime::fromTimestampWithMicroseconds($timestamp);

        self::assertSame($expected, $date->format('Y-m-d H:i:s.u'));
    }


    public static function createFromTimestampWithMicrosecondsProvider() : array
    {
        return [
            [1471690033123456, '2016-08-20 10:47:13.123456'],
            [1550839719096496, '2019-02-22 12:48:39.096496'],
            [1550839719000496, '2019-02-22 12:48:39.000496'],
            [1550839719000006, '2019-02-22 12:48:39.000006'],
            [1550839719000000, '2019-02-22 12:48:39.000000'],
        ];
    }


    /**
     * @covers ::fromIntlCalendar
     */
    public function testCreateFromIntlCalendar()
    {
        $calendar = \IntlCalendar::createInstance();

        $calendar->setTime(1471690033123);

        $date = DateTime::fromIntlCalendar($calendar);

        self::assertSame('2016-08-20 10:47:13.123000', $date->format('Y-m-d H:i:s.u'));
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
     * @covers ::isInTheFuture
     */
    public function testIsInTheFuture()
    {
        $now    = DateTime::now();
        $future = $now->add('PT1H');
        $past   = $now->subtract('PT1H');
        
        self::assertTrue($future->isInTheFuture());
        self::assertFalse($past->isInTheFuture());
        
        // Sleep for a short time in case the above manipulations and assertions happened in the same
        // microsecond.
        usleep(10);
        
        self::assertFalse($now->isInTheFuture());
    }
    
    
    /**
     * @covers ::isInThePast
     */
    public function testIsInThePast()
    {
        $now    = DateTime::now();
        $now    = DateTime::now();
        $future = $now->add('PT1H');
        $past   = $now->subtract('PT1H');
        
        self::assertTrue($past->isInThePast());
        self::assertFalse($future->isInThePast());
        
        // Sleep for a short time in case the above manipulations and assertions happened in the same
        // microsecond.
        usleep(10);
        
        self::assertTrue($now->isInThePast());
    }
    
    
    /**
     * @covers ::timestampWithMillisecond
     */
    public function testTimestampWithMillisecond()
    {
        $date = DateTime::create('2015-09-15T13:46:21.123456Z');
        
        self::assertSame(1442324781123, $date->timestampWithMillisecond());
    }
    
    
    /**
     * @covers ::timestampWithMicrosecond
     */
    public function testTimestampWithMicrosecond()
    {
        $date = DateTime::create('2015-09-15T13:46:21.123456Z');
        
        self::assertSame(1442324781123456, $date->timestampWithMicrosecond());
    }
    
    
    /**
     * @covers ::day
     * @covers ::dayOfMonth
     */
    public function testDay()
    {
        $date = DateTime::create('2015-09-15T13:46:21Z');
        
        self::assertSame(15, $date->day());
    }
    
    
    /**
     * @covers ::dayOfWeekLocal
     */
    public function testDayOfWeekLocal()
    {
        $date = DateTime::create('2016-08-01T00:00:00Z'); // Monday
        
        self::assertSame(1, $date->dayOfWeekLocal('en_GB'));
        self::assertSame(2, $date->dayOfWeekLocal('en_US'));
    }
    
    
    /**
     * @covers ::dayOfWeek
     */
    public function testDayOfWeek()
    {
        $date = DateTime::create('2016-08-01T00:00:00Z'); // Monday
        
        self::assertSame(1, $date->dayOfWeek());
    }
    
    
    /**
     * @covers ::daysInMonth
     */
    public function testDaysInMonth()
    {
        $expected = [
            1  => 31,
            2  => 28,
            3  => 31,
            4  => 30,
            5  => 31,
            6  => 30,
            7  => 31,
            8  => 31,
            9  => 30,
            10 => 31,
            11 => 30,
            12 => 31,
        ];
        
        foreach ($expected as $month => $days) {
            $date = DateTime::fromFormat('Y-n-d', sprintf('2015-%d-01', $month));
            self::assertSame($days, $date->daysInMonth());
        }
        
        // Ensure Feb is correct in leap years.
        self::assertSame(29, DateTime::fromFormat('Y-m-d', '2016-02-01')->daysInMonth());
    }
    
    
    /**
     * @covers ::daysRemainingInMonth
     */
    public function testDaysRemainingInMonth()
    {
        $date = DateTime::create('2015-09-15T13:46:21Z');
        
        self::assertSame(15, $date->daysRemainingInMonth());
    }
    
    
    /**
     * @covers ::microsecond
     */
    public function testMicrosecond()
    {
        $date = DateTime::create('2015-09-15T13:46:21.123456Z');
        
        self::assertSame(123456, $date->microsecond());
    }
    
    
    /**
     * @covers ::withTimeAt
     */
    public function testWithTimeAt()
    {
        $date = DateTime::create('2015-09-15T13:46:21Z')->withTimeAt(12, 5, 42, 987654321);
        
        self::assertSame('2015-09-15 12:05:42.987654', $date->format('Y-m-d H:i:s.u'));
    }
    
    
    /**
     * @covers ::withTimeAtMidnight
     */
    public function testWithTimeAtMidnight()
    {
        $date = DateTime::create('2015-09-15T13:46:21.123456Z')->withTimeAtMidnight();
        
        self::assertSame('2015-09-15 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
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
        $date = DateTime::create('2015-09-15T13:46:21Z');
        
        self::assertSame('2015-09-14T00:00:00+00:00', $date->withDateAtStartOfWeek("en_GB")->format('c'));
        self::assertSame('2015-09-13T00:00:00+00:00', $date->withDateAtStartOfWeek("en_US")->format('c'));
    }
    
    
    /**
     * @covers ::withDateAtStartOfMonth
     */
    public function testWithDateAtStartOfMonth()
    {
        $date = DateTime::create('2015-09-15T13:46:21Z')->withDateAtStartOfMonth();
        
        self::assertSame('2015-09-01T00:00:00+00:00', $date->format('c'));
    }
    
    
    /**
     * @covers ::withDateAtEndOfMonth
     */
    public function testWithDateAtEndOfMonth()
    {
        $date = DateTime::create('2015-09-15T13:46:21Z')->withDateAtEndOfMonth();
        
        self::assertSame('2015-09-30T00:00:00+00:00', $date->format('c'));
    }
    
    
    /**
     * @dataProvider withDateAtDayOfWeekInMonthProvider
     * @covers ::withDateAtDayOfWeekInMonth
     *
     * @param string $date
     * @param int    $dayOfWeek
     * @param int    $occurrence
     * @param string $expected
     */
    public function testWithDateAtDayOfWeekInMonth(string $date, int $dayOfWeek, int $occurrence, string $expected)
    {
        $date = DateTime::create($date)->withDateAtDayOfWeekInMonth($dayOfWeek, $occurrence);
        
        self::assertSameDate($expected, $date);
    }
    
    
    public function withDateAtDayOfWeekInMonthProvider() : array
    {
        return [
            ['2015-09-15 13:46:21', DateTime::MON, 3, '2015-09-21 13:46:21'],
            ['2015-09-15 13:46:21', DateTime::MON, 5, '2015-10-05 13:46:21'], // Overflow to next month.
        ];
    }


    /**
     * @dataProvider withTimezoneProvider
     * @covers ::withTimeZone
     *
     * @param string $initialTz
     * @param string $newTz
     * @param string $expected
     */
    public function testWithTimezone(string $initialTz, string $newTz, string $expected)
    {
        $before = DateTime::create($expected, new \DateTimeZone($initialTz));
        $after  = $before->withTimeZone(new \DateTimeZone($newTz));

        // String representations should be different.
        self::assertNotSame($before->format('c'), $after->format('c'));

        // Dates should be considered equal.
        self::assertSameDate($expected, $after);
    }


    public function withTimezoneProvider() : array
    {
        return [
            ['UTC', 'Asia/Tokyo', '2015-09-15 13:46:21'],
        ];
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
