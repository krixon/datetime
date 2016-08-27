<?php

namespace Krixon\DateTime;

use DateTimeZone;
use IntlCalendar;
use IntlTimeZone;

/**
 * A facade around \DateTime to ensure immutability while maintaining a single type of DateTime object.
 *
 * This works around the inconsistencies between \DateTime and \DateTimeImmutable and the \DateTimeInterface provided
 * by PHP. It also adds extra functionality.
 */
class DateTime implements \Serializable, \JsonSerializable
{
    /**
     * @var \DateTime
     */
    private $wrapped;
    
    
    public function __construct($time = 'now', DateTimeZone $timezone = null, string $format = null)
    {
        if ($format) {
            $this->wrapped = \DateTime::createFromFormat($format, $time, $timezone);
        } else {
            $this->wrapped = new \DateTime($time, $timezone);
        }
    }
    
    
    /**
     * @inheritdoc
     */
    public function __toString() : string
    {
        return $this->format(\DateTime::ISO8601);
    }
    
    
    /**
     * @inheritdoc
     */
    public function __clone()
    {
        $this->wrapped = clone $this->wrapped;
    }
    
    
    /**
     * @param string            $time
     * @param DateTimeZone|null $timezone
     *
     * @return self
     */
    public static function create($time = 'now', DateTimeZone $timezone = null) : self
    {
        return new static($time, $timezone);
    }
    
    
    /**
     * @param DateTimeZone|null $timezone
     *
     * @return self
     */
    public static function now(DateTimeZone $timezone = null) : self
    {
        // Ensure microsecond precision.
        
        list($microsecond, $second) = explode(' ', microtime());
        
        $microsecond *= 10 ** 6;
        $second *= 10 ** 6;
        $timestamp = $second + $microsecond;
        
        $instance = static::fromTimestampWithMicroseconds($timestamp);
        
        if ($timezone) {
            $instance->wrapped->setTimezone($timezone);
        }
        
        return $instance;
    }
    
    
    /**
     * @param string            $format
     * @param mixed             $time
     * @param DateTimeZone|null $timezone
     *
     * @return self
     */
    public static function fromFormat(string $format, $time, DateTimeZone $timezone = null) : self
    {
        return new static($time, $timezone, $format);
    }
    
    
    /**
     * @param int $timestamp
     *
     * @return self
     */
    public static function fromTimestamp(int $timestamp) : self
    {
        return new static("@$timestamp");
    }
    
    
    /**
     * Creates a new instance from a millisecond-precision unix timestamp.
     *
     * @param int $timestamp
     *
     * @return self
     */
    public static function fromTimestampWithMilliseconds(int $timestamp) : self
    {
        return static::fromTimestampWithMicroseconds($timestamp * 1000);
    }
    
    
    /**
     * Creates a new instance from a microsecond-precision unix timestamp.
     *
     * @param int $timestamp
     *
     * @return self
     */
    public static function fromTimestampWithMicroseconds(int $timestamp) : self
    {
        $seconds      = (int)($timestamp / 10 ** 6);
        $microseconds = $timestamp - ($seconds * 10 ** 6);
        
        return static::fromFormat('U.u', $seconds . '.' . $microseconds);
    }
    
    
    /**
     * Creates a new instance from an internal \DateTime object.
     *
     * @param \DateTime $date
     *
     * @return self
     */
    public static function fromInternalDateTime(\DateTime $date) : self
    {
        $instance = new static;
        
        $instance->wrapped = clone $date;
        
        return $instance;
    }
    
    
    /**
     * Creates a new instance from an internal \DateTime object.
     *
     * @param IntlCalendar $calendar
     *
     * @return self
     */
    public static function fromIntlCalendar(IntlCalendar $calendar) : self
    {
        return static::fromTimestampWithMilliseconds($calendar->getTime());
    }
    
    
    /**
     * Returns a new instance with the date set accordingly.
     *
     * Any omitted values will default to the current value.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @return self
     */
    public function withDateAt(int $year = null, int $month = null, int $day = null) : self
    {
        if (null === $year && null === $month && null === $day) {
            return $this;
        }
        
        $year  = $year === null ? $this->year() : $year;
        $month = $month ?: $this->month();
        $day   = $day   ?: $this->day();
        
        $instance = clone $this;
        
        $instance->wrapped->setDate($year, $month, $day);
        
        return $instance;
    }
    
    
    /**
     * @param int $year
     *
     * @return self
     */
    public function withYear(int $year) : self
    {
        return $this->withDateAt($year);
    }
    
    
    /**
     * @param int $month
     *
     * @return self
     */
    public function withMonth(int $month) : self
    {
        return $this->withDateAt(null, $month);
    }
    
    
    /**
     * @param int $day
     *
     * @return self
     */
    public function withDay(int $day) : self
    {
        return $this->withDateAt(null, null, $day);
    }
    
    
    /**
     * Returns a new instance with the time set accordingly.
     *
     * Any omitted values will default to the current value.
     *
     * @param int|null $hour
     * @param int|null $minute
     * @param int|null $second
     * @param int|null $microsecond
     *
     * @return self
     */
    public function withTimeAt(
        int $hour = null,
        int $minute = null,
        int $second = null,
        int $microsecond = null
    ) : self {
        
        $instance = clone $this;
        
        $hour        = $hour        === null ? $this->hour()        : $hour;
        $minute      = $minute      === null ? $this->minute()      : $minute;
        $second      = $second      === null ? $this->second()      : $second;
        $microsecond = $microsecond === null ? $this->microsecond() : $microsecond;
        
        $instance->wrapped->setTime($hour, $minute, $second);
        
        // There is no API for setting the microsecond explicitly so a new instance has to be constructed.
        $format            = 'Y-m-d H:i:s';
        $value             = $instance->format($format) . '.' . substr($microsecond, 0, 6);
        $instance->wrapped = \DateTime::createFromFormat("$format.u", $value);
        
        return $instance;
    }
    
    
    /**
     * Returns a new instance with the time set to midnight.
     *
     * @return self
     */
    public function withTimeAtMidnight() : self
    {
        return $this->withTimeAt(0, 0, 0, 0);
    }
    
    
    /**
     * Returns a new instance with the date set to 1st of Jan in the current year and time set to midnight.
     *
     * @return self
     */
    public function withDateAtStartOfYear() : self
    {
        $instance = $this->withTimeAtMidnight();
        
        $instance->wrapped->setDate($instance->format('Y'), 1, 1);
        
        return $instance;
    }
    
    
    /**
     * Returns a new instance with the date set to 1st of the current month and time set to midnight.
     *
     * @return self
     */
    public function withDateAtStartOfMonth() : self
    {
        $instance = $this->withTimeAtMidnight();
        
        $instance->wrapped->setDate($instance->year(), $instance->month(), 1);
        
        return $instance;
    }
    
    
    /**
     * Returns a new instance with the date set to last day of the current month and time set to midnight.
     *
     * @return self
     */
    public function withDateAtEndOfMonth() : self
    {
        $instance = $this->withDateAtStartOfMonth();
        
        $instance->wrapped->modify('+1 month');
        $instance->wrapped->modify('-1 day');
        
        return $instance;
    }
    
    
    /**
     * Returns a new instance with the date set to 1st of the current month and time set to midnight.
     *
     * @param string|null $locale The locale with which to determine the week start day. If not set the default locale
     *                            will be used.
     *
     * @return self
     */
    public function withDateAtStartOfWeek(string $locale = null) : self
    {
        $instance = $this->withTimeAtMidnight();
        $calendar = $instance->toIntlCalendar($locale);
        
        $calendar->set(IntlCalendar::FIELD_DOW_LOCAL, 1);
        
        $instance->wrapped->setTimestamp((int)($calendar->getTime() / 1000));
        
        return $instance;
    }
    
    
    /**
     * Returns an internal, mutable \DateTime object with the same value as this instance.
     *
     * @return \DateTime
     */
    public function toInternalDateTime() : \DateTime
    {
        return clone $this->wrapped;
    }
    
    
    /**
     * Returns an IntlCalendar instance set to the datetime represented by this instance.
     *
     * @param string|null $locale
     *
     * @return IntlCalendar
     */
    public function toIntlCalendar(string $locale = null) : IntlCalendar
    {
        $timezone = IntlTimeZone::createTimeZone($this->timezone()->getName());
        $calendar = IntlCalendar::createInstance($timezone, $locale);
        
        $calendar->setTime($this->timestampWithMillisecond());
        
        return $calendar;
    }
    
    
    /**
     * @param self $other
     * @param bool $absolute
     *
     * @return DateInterval
     */
    public function diff(self $other, $absolute = false) : DateInterval
    {
        return DateInterval::diff($this, $other, $absolute);
    }
    
    
    /**
     * @param $format
     *
     * @return string
     */
    public function format($format) : string
    {
        return $this->wrapped->format($format);
    }
    
    
    /**
     * @return int
     */
    public function offset() : int
    {
        return $this->wrapped->getOffset();
    }
    
    
    /**
     * The year as an integer.
     *
     * @return int
     */
    public function year() : int
    {
        return (int)$this->format('Y');
    }
    
    
    /**
     * The month as an integer from 1-12.
     *
     * @return int
     */
    public function month() : int
    {
        return (int)$this->format('n');
    }
    
    
    /**
     * Alias of dayOfMonth.
     *
     * @return int
     */
    public function day() : int
    {
        return $this->dayOfMonth();
    }
    
    
    /**
     * The day of the year as an integer from 1-366.
     *
     * @return int
     */
    public function dayOfYear() : int
    {
        return $this->format('z') + 1;
    }
    
    
    /**
     * The day of the month as an integer from 1-31.
     *
     * @return int
     */
    public function dayOfMonth() : int
    {
        return (int)$this->format('j');
    }
    
    
    /**
     * Returns the day of the week for the specified locale.
     *
     * 1 is the first day of the week, 2 the second etc. For example, for en_GB a Monday would be 1 but for en_US a
     * Monday would be 2 as the first day of the week is Sunday in that locale.
     *
     * @param string|null $locale
     *
     * @return int
     */
    public function dayOfWeek(string $locale = null) : int
    {
        $calendar = $this->toIntlCalendar($locale);
        
        return (int)$calendar->get(IntlCalendar::FIELD_DOW_LOCAL);
    }
    
    
    /**
     * Returns the ISO8601 day of the week. Monday is always 1.
     *
     * @return int
     */
    public function dayOfWeekIso() : int
    {
        return (int)$this->format('N');
    }
    
    
    /**
     * The number of days in the current year. Either 365 or 366 if this is a leap year.
     *
     * @return int
     */
    public function daysInYear() : int
    {
        return $this->isLeapYear() ? 366 : 365;
    }
    
    
    /**
     * The number of days in the current month as an integer from 28-31.
     *
     * @return int
     */
    public function daysInMonth() : int
    {
        return (int)$this->format('t');
    }
    
    
    /**
     * The number of days remaining in the current year as an integer from 0 to 366.
     *
     * Note that this ignores the time.
     *
     * @return int
     */
    public function daysRemainingInYear() : int
    {
        return $this->daysInYear() - $this->dayOfYear();
    }
    
    
    /**
     * The number of days remaining in the current month as an integer from 0 - 31.
     *
     * Note that this ignores the time.
     *
     * @return int
     */
    public function daysRemainingInMonth() : int
    {
        return $this->daysInMonth() - $this->dayOfMonth();
    }
    
    
    /**
     * The number of days remaining in the current week as an integer from 0 - 31.
     *
     * Note that this ignores the time.
     *
     * @param string|null $locale
     *
     * @return int
     */
    public function daysRemainingInWeek(string $locale = null) : int
    {
        return 7 - $this->dayOfWeek($locale);
    }
    
    
    /**
     * @return int
     */
    public function hour() : int
    {
        return (int)$this->format('G');
    }
    
    
    /**
     * @return int
     */
    public function minute() : int
    {
        return (int)$this->format('i');
    }
    
    
    /**
     * @return int
     */
    public function second() : int
    {
        return (int)$this->format('s');
    }
    
    
    /**
     * @return int
     */
    public function microsecond() : int
    {
        return (int)$this->format('u');
    }
    
    
    /**
     * The unix timestamp.
     *
     * @return int
     */
    public function timestamp() : int
    {
        return $this->wrapped->getTimestamp();
    }
    
    
    /**
     * The unix timestamp in milliseconds (1/1000s).
     *
     * @return int
     */
    public function timestampWithMillisecond() : int
    {
        return (int)($this->timestampWithMicrosecond() / 1000);
    }
    
    
    /**
     * The unix timestamp in microseconds (1/1000000s).
     *
     * @return int
     */
    public function timestampWithMicrosecond() : int
    {
        return (int)($this->format('Uu'));
    }
    
    
    /**
     * @return DateTimeZone
     */
    public function timezone() : DateTimeZone
    {
        return $this->wrapped->getTimezone();
    }
    
    
    /**
     * @param self $other
     *
     * @return bool
     */
    public function equals(self $other) : bool
    {
        return $this->wrapped == $other->wrapped;
    }
    
    
    /**
     * @param self $other
     *
     * @return bool
     */
    public function isLaterThan(self $other) : bool
    {
        return static::compare($this, $other) === 1;
    }
    
    
    /**
     * @param self $other
     *
     * @return bool
     */
    public function isLaterThanOrEqualTo(self $other) : bool
    {
        return static::compare($this, $other) >= 0;
    }
    
    
    /**
     * @param self $other
     *
     * @return bool
     */
    public function isEarlierThan(self $other) : bool
    {
        return static::compare($this, $other) === -1;
    }
    
    
    /**
     * @param self $other
     *
     * @return bool
     */
    public function isEarlierThanOrEqualTo(self $other) : bool
    {
        return static::compare($this, $other) <= 0;
    }
    
    
    /**
     * @return bool
     */
    public function isInThePast() : bool
    {
        return $this->isEarlierThan(static::now());
    }
    
    
    /**
     * @return bool
     */
    public function isInTheFuture() : bool
    {
        return $this->isLaterThan(static::now());
    }
    
    
    /**
     * @return bool
     */
    public function isLeapYear() : bool
    {
        return (bool)$this->format('L');
    }
    
    
    /**
     * @param self $a
     * @param self $b
     *
     * @return int
     */
    public static function compare(self $a, self $b) : int
    {
        return $a->wrapped <=> $b->wrapped;
    }
    
    
    /**
     * Creates a new instance with the interval subtracted from it.
     *
     * @param DateInterval|string $interval A DateInterval instance or a DateInterval spec as a string.
     *
     * @return self
     */
    public function subtract($interval) : self
    {
        $instance = clone $this;
        
        $instance->wrapped->sub(self::resolveDateInterval($interval)->toInternalDateInterval());
        
        return $instance;
    }
    
    
    /**
     * Creates a new instance with the interval added to it.
     *
     * @param DateInterval|string $interval A DateInterval instance or a DateInterval spec as a string.
     *
     * @return self
     */
    public function add($interval) : self
    {
        $instance = clone $this;
        
        $instance->wrapped->add(self::resolveDateInterval($interval)->toInternalDateInterval());
        
        return $instance;
    }
    
    
    /**
     * Creates a new instance modified according to the specification.
     *
     * For valid formats @see http://php.net/manual/en/datetime.formats.relative.php
     *
     * @param string $specification
     *
     * @return self
     */
    public function modify(string $specification) : self
    {
        $instance = clone $this;
        
        $instance->wrapped->modify($specification);
        
        return $instance;
    }
    
    
    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->wrapped);
    }
    
    
    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->wrapped = unserialize($serialized);
    }
    
    
    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }
    
    
    /**
     * @param DateInterval|string $interval
     *
     * @return DateInterval
     */
    private static function resolveDateInterval($interval) : DateInterval
    {
        if (!$interval instanceof DateInterval) {
            // No need for any special validation here; delegate that to DateInterval.
            $interval = DateInterval::fromSpecification($interval);
        }
        
        return $interval;
    }
}
