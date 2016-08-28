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
    
    
    /**
     * @var DateTime
     */
    private $base;
    
    /**
     * @var \DateTime
     */
    private $date;
    
    
    /**
     * @param DateTime $date
     */
    public function __construct(DateTime $date)
    {
        $this->base = $date;
        $this->date = $date->toInternalDateTime();
    }
    
    
    /**
     * @param DateTime $date
     *
     * @return self
     */
    public static function basedOn(DateTime $date) : self
    {
        return new static($date);
    }
    
    
    /**
     * @return DateTime
     */
    public function result() : DateTime
    {
        return DateTime::fromInternalDateTime($this->date);
    }
    
    
    /**
     * Clears the current calculation and returns to the base date.
     */
    public function clear()
    {
        $this->date = $this->base->toInternalDateTime();
    }
    
    
    /**
     * @param DateInterval|\DateInterval|string $interval
     */
    public function add($interval)
    {
        $interval = self::resolveDateInterval($interval);
        
        $this->date->add($interval);
    }
    
    
    /**
     * @param DateInterval|\DateInterval|string $interval
     */
    public function subtract($interval)
    {
        $interval = self::resolveDateInterval($interval);
        
        $this->date->sub($interval);
    }
    
    
    /**
     * @param string $modification
     */
    public function modify(string $modification)
    {
        $this->date->modify($modification);
    }
}
