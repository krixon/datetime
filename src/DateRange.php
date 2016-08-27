<?php

namespace Krixon\DateTime;

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
     * @var DateInterval
     */
    private $dateDiff;
    
    /**
     * @var DateInterval
     */
    private $fullDiff;
    
    
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
    public function from() : DateTime
    {
        return $this->from;
    }
    
    
    /**
     * The date at the end of the range.
     *
     * @return DateTime
     */
    public function until() : DateTime
    {
        return $this->until;
    }
    
    
    /**
     * An interval representing the difference between the from and until dates.
     *
     * @return DateInterval
     */
    public function diff() : DateInterval
    {
        return clone $this->fullDiff();
    }
    
    
    /**
     * Determines if the range contains a specified date and time.
     *
     * @param DateTime $dateTime
     *
     * @return bool
     */
    public function contains(DateTime $dateTime) : bool
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
     * @param self $other
     *
     * @return bool
     */
    public function equals(self $other) : bool
    {
        if ($other === $this) {
            return true;
        }
        
        return $other->from->equals($this->from) && $other->until->equals($this->until);
    }
    
    
    /**
     * The total number of years in the range.
     *
     * @return int
     */
    public function totalYears() : int
    {
        return $this->dateDiff()->years();
    }
    
    
    /**
     * The total number of months in the range.
     *
     * @return int
     */
    public function totalMonths() : int
    {
        return ($this->totalYears() * 12) + $this->dateDiff()->months();
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
        return $this->totalDays() / 7;
    }
    
    
    /**
     * The total number of days in the range.
     *
     * @return int
     */
    public function totalDays() : int
    {
        return $this->dateDiff()->totalDays();
    }
    
    
    /**
     * A diff between the two dates with times set to midnight to guarantee resolution to whole days.
     *
     * @return DateInterval
     */
    private function dateDiff() : DateInterval
    {
        if (null === $this->dateDiff) {
            $this->dateDiff = $this->from->withTimeAtMidnight()->diff($this->until->withTimeAtMidnight());
        }
        
        return $this->dateDiff;
    }
    
    
    /**
     * A full diff between the two dates.
     *
     * @return DateInterval
     */
    private function fullDiff() : DateInterval
    {
        if (null === $this->fullDiff) {
            $this->fullDiff = $this->from->diff($this->until);
        }
        
        return $this->fullDiff;
    }
}
