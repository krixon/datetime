<?php

namespace Krixon\DateTime;

/**
 * Provides a mechanism for carrying out efficient date arithmetic.
 *
 * Although DateTime objects themselves provide arithmetic functionality, because they are immutable  it is sometimes
 * useful to perform many calculations without creating intermediate objects which are immediately thrown away. This
 * calculator allows arithmetic to be performed on a base DateTime instance before returning a final instance with
 * the result.
 */
class DateTimeCalculator
{
    use ProvidesDateTimeInformation,
        ResolvesDateIntervals;
    
    const YEAR          = \IntlCalendar::FIELD_YEAR;
    const MONTH         = \IntlCalendar::FIELD_MONTH;
    const WEEK_OF_YEAR  = \IntlCalendar::FIELD_WEEK_OF_YEAR;
    const WEEK_OF_MONTH = \IntlCalendar::FIELD_WEEK_OF_MONTH;
    const DAY_OF_YEAR   = \IntlCalendar::FIELD_DAY_OF_YEAR;
    const DAY_OF_MONTH  = \IntlCalendar::FIELD_DAY_OF_MONTH;
    const DAY_OF_WEEK   = \IntlCalendar::FIELD_DAY_OF_WEEK;
    const HOUR          = \IntlCalendar::FIELD_HOUR_OF_DAY;
    const MINUTE        = \IntlCalendar::FIELD_MINUTE;
    const SECOND        = \IntlCalendar::FIELD_SECOND;
    
    
    /**
     * @var DateTime
     */
    private $base;
    
    /**
     * @var \DateTime
     */
    private $date;
    
    /**
     * @var string
     */
    private $locale;
    
    /**
     * @var \IntlCalendar
     */
    private $calendar;
    
    /**
     * @var bool
     */
    private $dateSyncRequired = false;
    
    /**
     * @var bool
     */
    private $calendarSyncRequired = false;
    
    
    /**
     * @param DateTime $date
     * @param string   $locale
     */
    public function __construct(DateTime $date, string $locale = null)
    {
        $this->base   = $date;
        $this->locale = $locale;
        
        $this->clear();
    }
    
    
    /**
     * @inheritdoc
     */
    public function __clone()
    {
        $this->date     = clone $this->date;
        $this->calendar = clone $this->calendar;
    }
    
    
    /**
     * @param DateTime $date
     * @param string   $locale
     *
     * @return DateTimeCalculator
     */
    public static function basedOn(DateTime $date, string $locale = null) : self
    {
        return new static($date, $locale);
    }
    
    
    /**
     * @return DateTime
     */
    public function result() : DateTime
    {
        return DateTime::fromInternalDateTime($this->date());
    }
    
    
    /**
     * Clears the current calculation and returns to the base date.
     */
    public function clear()
    {
        $this->date     = $this->base->toInternalDateTime();
        $this->calendar = $this->createCalendar($this->locale);
        
        if (null === $this->locale) {
            $this->calendar->setFirstDayOfWeek(\IntlCalendar::DOW_MONDAY);
        }
    }
    
    
    /**
     * @param int $field
     * @param int $amount
     */
    public function add(int $field, int $amount)
    {
        self::assertValidField($field);
        
        $this->calendar()->add($field, $amount);
        
        $this->dateSyncRequired = true;
    }
    
    
    /**
     * @param int $field
     * @param int $amount
     */
    public function subtract(int $field, int $amount)
    {
        self::assertValidField($field);
        
        $this->calendar()->add($field, -$amount);
        
        $this->dateSyncRequired = true;
    }
    
    
    /**
     * @param int $field
     * @param int $amount
     */
    public function rollForward(int $field, int $amount)
    {
        self::assertValidField($field);
        
        $this->calendar()->roll($field, $amount);
        
        $this->dateSyncRequired = true;
    }
    
    
    /**
     * @param int $field
     * @param int $amount
     */
    public function rollBackward(int $field, int $amount)
    {
        self::assertValidField($field);
        
        $this->calendar()->roll($field, -$amount);
        
        $this->dateSyncRequired = true;
    }
    
    
    /**
     * @param DateInterval|\DateInterval|string $interval
     */
    public function addInterval($interval)
    {
        $interval = self::resolveDateInterval($interval);
        
        $this->date()->add($interval);
    
        $this->calendarSyncRequired = true;
    }
    
    
    /**
     * @param DateInterval|\DateInterval|string $interval
     */
    public function subtractInterval($interval)
    {
        $interval = self::resolveDateInterval($interval);
        
        $this->date()->sub($interval);
    
        $this->calendarSyncRequired = true;
    }
    
    
    /**
     * @param string $modification
     */
    public function modify(string $modification)
    {
        $this->date()->modify($modification);
        
        $this->calendarSyncRequired = true;
    }
    
    
    /**
     * @inheritdoc
     */
    protected function date() : \DateTime
    {
        if ($this->dateSyncRequired) {
            $this->syncDate();
            $this->dateSyncRequired = false;
        }
        
        return $this->date;
    }
    
    
    private function calendar() : \IntlCalendar
    {
        if ($this->calendarSyncRequired) {
            $this->syncCalendar();
            $this->calendarSyncRequired = false;
        }
        
        return $this->calendar;
    }
    
    
    private function syncCalendar()
    {
        $this->calendar->setTime($this->timestampWithMillisecond());
    }
    
    
    private function syncDate()
    {
        $this->date->setTimestamp($this->calendar->getTime() / 1000);
    }
    
    
    /**
     * @param int $field
     */
    private static function assertValidField(int $field)
    {
        static $fields = [
            self::DAY_OF_MONTH,
            self::DAY_OF_WEEK,
            self::DAY_OF_YEAR,
            self::HOUR,
            self::MINUTE,
            self::MONTH,
            self::SECOND,
            self::WEEK_OF_MONTH,
            self::WEEK_OF_YEAR,
            self::YEAR,
        ];
        
        if (!in_array($field, $fields, true)) {
            throw new \InvalidArgumentException("Invalid field: $field.");
        }
    }
}
