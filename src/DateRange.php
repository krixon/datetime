<?php

namespace Krixon\DateTime;

use DateInterval;

/**
 * A period of time between two dates.
 */
class DateRange
{
    /**
     * @var DateTime
     */
    private $from;
    
    /**
     * @var DateTime
     */
    private $until;
    
    
    /**
     * @param DateTime $from
     * @param DateTime $until
     */
    public function __construct(DateTime $from, DateTime $until)
    {
        if ($from->isLaterThan($until)) {
            list($from, $until) = [$until, $from];
        }
        
        $this->from  = $from;
        $this->until = $until;
    }
    
    
    /**
     * The date at the start of the range.
     *
     * @return DateTime
     */
    public function from()
    {
        return $this->from;
    }
    
    
    /**
     * The date at the end of the range.
     *
     * @return DateTime
     */
    public function until()
    {
        return $this->until;
    }
    
    
    /**
     * An interval representing the difference between the from and until dates.
     *
     * @return bool|DateInterval
     */
    public function diff()
    {
        return $this->from->diff($this->until);
    }
    
    
    /**
     * Determines if the range contains a specified date and time.
     *
     * @param DateTime $dateTime
     *
     * @return bool
     */
    public function contains(DateTime $dateTime)
    {
        return $this->from->isEarlierThanOrEqualTo($dateTime) && $this->until->isLaterThan($dateTime);
    }
    
    
    /**
     * Determines if the range contains the current date and time.
     *
     * @return bool
     */
    public function containsNow() : bool
    {
        return $this->contains(DateTime::now());
    }
    
    
    /**
     * Determines if an instance is equal to this instance.
     *
     * @param DateRange $other
     *
     * @return bool
     */
    public function equals(DateRange $other) : bool
    {
        if ($other === $this) {
            return true;
        }
        
        return $other->from->equals($this->from) && $other->until->equals($this->until);
    }
    
    
    /**
     * The total number of days in the range.
     *
     * @return int
     */
    public function totalDays() : int
    {
        return $this->from->withTimeAtMidnight()->diff($this->until->withTimeAtMidnight())->days;
    }
    
    
    /**
     * The total number of whole weeks in the range.
     *
     * Note that this does not include partial weeks, so a range spanning 15 days will return 2.
     *
     * This does not consider weeks to be whole based on any particular start day - this is simply the total number
     * of days divided by 7.
     *
     * @return int
     */
    public function totalWeeks() : int
    {
        return (int)($this->totalDays() / 7);
    }
}
