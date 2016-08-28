<?php

namespace Krixon\DateTime;

trait ProvidesDateTimeInformation
{
    /**
     * @var \DateTime
     */
    private $date;
    
    
    /**
     * @param string $format
     *
     * @return string
     */
    public function format(string $format) : string
    {
        return $this->date->format($format);
    }
    
    
    /**
     * @return int
     */
    public function year() : int
    {
        return $this->format('Y');
    }
    
    
    /**
     * @return int
     */
    public function month() : int
    {
        return $this->format('n');
    }
    
    
    /**
     * @return int
     */
    public function day() : int
    {
        return $this->format('j');
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
        return $this->day();
    }
    
    
    /**
     * Returns the ISO8601 day of the week. Monday is always 1.
     *
     * @return int
     */
    public function dayOfWeek() : int
    {
        return $this->format('N');
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
    public function dayOfWeekLocal(string $locale = null) : int
    {
        $calendar = $this->calendar($locale);
        
        return (int)$calendar->get(\IntlCalendar::FIELD_DOW_LOCAL);
    }
    
    
    /**
     * @return int
     */
    public function hour() : int
    {
        return $this->format('G');
    }
    
    
    /**
     * @return int
     */
    public function minute() : int
    {
        return $this->format('i');
    }
    
    
    /**
     * @return int
     */
    public function second() : int
    {
        return $this->format('s');
    }
    
    
    /**
     * @return int
     */
    public function microsecond() : int
    {
        return $this->format('u');
    }
    
    
    /**
     * The unix timestamp.
     *
     * @return int
     */
    public function timestamp() : int
    {
        return $this->date->getTimestamp();
    }
    
    
    /**
     * The unix timestamp in milliseconds (1/1000s).
     *
     * @return int
     */
    public function timestampWithMillisecond() : int
    {
        return $this->timestampWithMicrosecond() / 1000;
    }
    
    
    /**
     * The unix timestamp in microseconds (1/1000000s).
     *
     * @return int
     */
    public function timestampWithMicrosecond() : int
    {
        return $this->format('Uu');
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
        return $this->format('t');
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
     * The number of days remaining in the current week as an integer from 0 - 7 based on Monday being day 1.
     *
     * Note that this ignores the time.
     *
     * @return int
     */
    public function daysRemainingInWeek() : int
    {
        return 7 - $this->dayOfWeek();
    }
    
    
    /**
     * The number of days remaining in the current week as an integer from 0 - 7.
     *
     * Note that this ignores the time.
     *
     * @param string|null $locale
     *
     * @return int
     */
    public function daysRemainingInWeekLocal(string $locale = null) : int
    {
        return 7 - $this->dayOfWeekLocal($locale);
    }
    
    
    /**
     * @return bool
     */
    public function isLeapYear() : bool
    {
        return $this->format('L');
    }
    
    
    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function equals(DateTime $date) : bool
    {
        return $this->compare($date) === 0;
    }
    
    
    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isLaterThan(DateTime $date) : bool
    {
        return $this->compare($date) === 1;
    }
    
    
    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isLaterThanOrEqualTo(DateTime $date) : bool
    {
        return $this->compare($date) >= 0;
    }
    
    
    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isEarlierThan(DateTime $date) : bool
    {
        return $this->compare($date) === -1;
    }
    
    
    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isEarlierThanOrEqualTo(DateTime $date) : bool
    {
        return $this->compare($date) <= 0;
    }
    
    
    /**
     * @return bool
     */
    public function isInThePast() : bool
    {
        return $this->isEarlierThan(DateTime::now());
    }
    
    
    /**
     * @return bool
     */
    public function isInTheFuture() : bool
    {
        return $this->isLaterThan(DateTime::now());
    }
    
    
    /**
     * @param string|null $locale
     *
     * @return \IntlCalendar
     */
    protected function calendar(string $locale = null) : \IntlCalendar
    {
        $timezone = \IntlTimeZone::createTimeZone($this->date->getTimezone()->getName());
        $calendar = \IntlCalendar::createInstance($timezone, $locale);
        
        $calendar->setTime($this->timestampWithMillisecond());
        
        return $calendar;
    }
    
    
    /**
     * @param DateTime $date
     *
     * @return int
     */
    private function compare(DateTime $date) : int
    {
        return $this->timestampWithMicrosecond() <=> $date->timestampWithMicrosecond();
    }
}
