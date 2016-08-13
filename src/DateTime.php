<?php

namespace Krixon\DateTime;

use DateInterval;
use DateTimeZone;

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
     * @return DateTime
     */
    public static function create($time = 'now', DateTimeZone $timezone = null) : DateTime
    {
        return new static($time, $timezone);
    }
    
    
    /**
     * @param DateTimeZone|null $timezone
     *
     * @return DateTime
     */
    public static function now(DateTimeZone $timezone = null) : DateTime
    {
        return new static('now', $timezone);
    }
    
    
    /**
     * @param string            $format
     * @param mixed             $time
     * @param DateTimeZone|null $timezone
     *
     * @return DateTime
     */
    public static function fromFormat(string $format, $time, DateTimeZone $timezone = null) : DateTime
    {
        return new static($time, $timezone, $format);
    }
    
    
    /**
     * @param int $timestamp
     *
     * @return DateTime
     */
    public static function fromTimestamp(int $timestamp) : DateTime
    {
        return new static("@$timestamp");
    }
    
    
    /**
     * Returns a new instance with the time set accordingly.
     *
     * @param int $hour
     * @param int $minute
     * @param int $second
     *
     * @return DateTime
     */
    public function withTimeAt(int $hour = 0, int $minute = 0, int $second = 0) : DateTime
    {
        $instance = clone $this;
        
        $instance->wrapped->setTime($hour, $minute, $second);
        
        return $instance;
    }
    
    
    /**
     * Returns a new instance with the date set accordingly.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @return DateTime
     */
    public function withDateAt(int $year, int $month = 1, int $day = 1) : DateTime
    {
        $instance = clone $this;
    
        $instance->wrapped->setDate($year, $month, $day);
    
        return $instance;
    }
    
    
    /**
     * Returns a new instance with the time set to midnight.
     *
     * @return DateTime
     */
    public function withTimeAtMidnight() : DateTime
    {
        return $this->withTimeAt(0, 0, 0);
    }
    
    
    /**
     * Returns a new instance with the date set to 1st of Jan in the current year and time set to midnight.
     *
     * @return DateTime
     */
    public function withDateAtStartOfYear() : DateTime
    {
        $instance = $this->withTimeAtMidnight();
        
        $instance->wrapped->setDate($instance->format('Y'), 1, 1);
        
        return $instance;
    }
    
    
    /**
     * Returns a new instance with the date set to 1st of the current month and time set to midnight.
     *
     * @return DateTime
     */
    public function withDateAtStartOfMonth() : DateTime
    {
        $instance = $this->withTimeAtMidnight();
        
        $instance->wrapped->setDate($instance->format('Y'), $instance->format('n'), 1);
        
        return $instance;
    }
    
    
    /**
     * Returns a new instance with the date set to 1st of the current month and time set to midnight.
     *
     * @param string|null $locale The locale with which to determine the week start day. If not set the default locale
     *                            will be used.
     *
     * @return DateTime
     */
    public function withDateAtStartOfWeek(string $locale = null) : DateTime
    {
        $instance = $this->withTimeAtMidnight();
        $calendar = \IntlCalendar::createInstance(null, $locale);
        
        $calendar->setTime($instance->timestamp() * 1000);
        $calendar->set(\IntlCalendar::FIELD_DOW_LOCAL, 1);
        
        $instance->wrapped->setTimestamp((int)($calendar->getTime() / 1000));
        
        return $instance;
    }
    
    
    /**
     * @param DateTime $other
     * @param bool     $absolute
     *
     * @return bool|DateInterval
     */
    public function diff(DateTime $other, $absolute = false)
    {
        return $this->wrapped->diff($other->wrapped, $absolute);
    }
    
    
    /**
     * @param $format
     *
     * @return string
     */
    public function format($format)
    {
        return $this->wrapped->format($format);
    }
    
    
    /**
     * @return int
     */
    public function offset()
    {
        return $this->wrapped->getOffset();
    }
    
    
    /**
     * @return int
     */
    public function timestamp()
    {
        return $this->wrapped->getTimestamp();
    }
    
    
    /**
     * @return \DateTimeZone
     */
    public function timezone()
    {
        return $this->wrapped->getTimezone();
    }
    
    
    /**
     * @param DateTime $other
     *
     * @return bool
     */
    public function equals(DateTime $other)
    {
        return $this->wrapped == $other->wrapped;
    }
    
    
    /**
     * @param DateTime $other
     *
     * @return bool
     */
    public function isLaterThan(DateTime $other)
    {
        return static::compare($this, $other) === 1;
    }
    
    
    /**
     * @param DateTime $other
     *
     * @return bool
     */
    public function isLaterThanOrEqualTo(DateTime $other)
    {
        return static::compare($this, $other) >= 0;
    }
    
    
    /**
     * @param DateTime $other
     *
     * @return bool
     */
    public function isEarlierThan(DateTime $other)
    {
        return static::compare($this, $other) === -1;
    }
    
    
    /**
     * @param DateTime $other
     *
     * @return bool
     */
    public function isEarlierThanOrEqualTo(DateTime $other)
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
     * @param DateTime $a
     * @param DateTime $b
     *
     * @return int
     */
    public static function compare(DateTime $a, DateTime $b)
    {
        return $a->wrapped <=> $b->wrapped;
    }
    
    
    /**
     * @param DateInterval|string $interval A DateInterval instance or a DateInterval spec as a string.
     *
     * @return DateTime
     */
    public function subtract($interval)
    {
        $instance = clone $this;
        
        $instance->wrapped->sub(self::resolveDateInterval($interval));
        
        return $instance;
    }
    
    
    /**
     * @param DateInterval|string $interval A DateInterval instance or a DateInterval spec as a string.
     *
     * @return DateTime
     */
    public function add($interval)
    {
        $instance = clone $this;
        
        $instance->wrapped->add(self::resolveDateInterval($interval));
        
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
        if (!$interval instanceof \DateInterval) {
            // No need for any special validation here; delegate that to \DateInterval.
            $interval = new \DateInterval($interval);
        }
        
        return $interval;
    }
}
