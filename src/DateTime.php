<?php

namespace Krixon\DateTime;

use DateTimeZone;
use IntlCalendar;

/**
 * A facade around \DateTime to ensure immutability while maintaining a single type of DateTime object.
 *
 * This works around the inconsistencies between \DateTime and \DateTimeImmutable and the \DateTimeInterface provided
 * by PHP. It also adds extra functionality.
 */
class DateTime implements \Serializable, \JsonSerializable
{
    use ProvidesDateTimeInformation,
        ResolvesDateIntervals;
    
    
    public function __construct($time = 'now', DateTimeZone $timezone = null, string $format = null)
    {
        if ($format) {
            $this->date = \DateTime::createFromFormat($format, $time, $timezone);
        } else {
            $this->date = new \DateTime($time, $timezone);
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
        $this->date = clone $this->date;
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
            $instance->date->setTimezone($timezone);
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
        
        $instance->date = clone $date;
        
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
        
        $instance->date->setDate($year, $month, $day);
        
        return $instance;
    }
    
    
    /**
     * Returns a new instance with the date set to 1st of Jan in the current year and time set to midnight.
     *
     * @return self
     */
    public function withDateAtStartOfYear() : self
    {
        $instance = $this->withTimeAtMidnight();
        
        $instance->date->setDate($instance->format('Y'), 1, 1);
        
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
        
        $instance->date->setDate($instance->year(), $instance->month(), 1);
        
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
        
        $instance->date->modify('+1 month');
        $instance->date->modify('-1 day');
        
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
        
        $instance->date->setTimestamp((int)($calendar->getTime() / 1000));
        
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
        
        $instance->date->setTime($hour, $minute, $second);
        
        // There is no API for setting the microsecond explicitly so a new instance has to be constructed.
        $format         = 'Y-m-d H:i:s';
        $value          = $instance->format($format) . '.' . substr($microsecond, 0, 6);
        $instance->date = \DateTime::createFromFormat("$format.u", $value);
        
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
     * Returns an internal, mutable \DateTime object with the same value as this instance.
     *
     * @return \DateTime
     */
    public function toInternalDateTime() : \DateTime
    {
        return clone $this->date;
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
        return $this->calendar($locale);
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
     * @return int
     */
    public function offset() : int
    {
        return $this->date->getOffset();
    }
    
    
    
    /**
     * @return DateTimeZone
     */
    public function timezone() : DateTimeZone
    {
        return $this->date->getTimezone();
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
        
        $instance->date->sub(self::resolveDateInterval($interval));
        
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
        
        $instance->date->add(self::resolveDateInterval($interval));
        
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
        
        $instance->date->modify($specification);
        
        return $instance;
    }
    
    
    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->date);
    }
    
    
    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->date = unserialize($serialized);
    }
    
    
    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }
}
