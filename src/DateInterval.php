<?php

namespace Krixon\DateTime;

/**
 * Immutable facade around \DateInterval.
 *
 * Although not part of the ISO8601 specification, this supports the additional duration identifier U for specifying
 * microseconds. For example PT42U represents a duration of 42µs.
 *
 * This also supports microsecond resolution when created from date strings or diffs from DateTime or DateRange.
 *
 * TODO: Support decimal fraction in the smallest component. This means disallowing it for microseconds if it appears
 * elsewhere, and supporting it in microseconds.
 */
class DateInterval
{
    /**
     * @var \DateInterval
     */
    private $wrapped;
    
    /**
     * @var int
     */
    private $microseconds = 0;

    /**
     * @var int
     */
    private $totalMicroseconds;
    
    
    /**
     * @param \DateInterval $wrapped
     * @param int           $microseconds
     */
    private function __construct(\DateInterval $wrapped, int $microseconds = 0)
    {
        $this->wrapped      = $wrapped;
        $this->microseconds = $microseconds;
    }
    
    
    /**
     * @inheritdoc
     */
    public function __clone()
    {
        $this->wrapped = self::clone($this->wrapped);
    }
    
    
    /**
     * Creates a new interval from a specification string.
     *
     * The specification follows ISO8601 with the exception that microseconds are supported via the U designator.
     *
     * @see https://en.wikipedia.org/wiki/ISO_8601#Durations
     *
     * @param string $specification
     *
     * @return self
     */
    public static function fromSpecification(string $specification) : self
    {
        $microseconds = 0;
        
        // Parse the microsecond component.
        if (false !== ($position = stripos($specification, 'U'))) {
            // Step backwards consuming digits until we hit a duration designator.
            // Note that we always expect at least the duration designator (P), but for loop safety we break if
            // the first character in the specification is reached too.
            
            $microseconds = '';
            
            while ($position > 0) {
                $char = $specification[--$position];
                
                if (!is_numeric($char)) {
                    break;
                }
                
                $microseconds = $char . $microseconds;
            }
            
            // Remove the microsecond designator from the specification.
            $specification = substr($specification, 0, -1 - strlen($microseconds));
        }
        
        // If the specification is just the duration designator it means that only microseconds were specified.
        // In that case we  create an empty interval for convenience.
        if ('P' === $specification || 'PT' === $specification) {
            $specification = 'P0Y';
        }
        
        return new static(new \DateInterval($specification), (int)$microseconds);
    }
    
    
    /**
     * Returns an interval representing the difference between two dates.
     *
     * @param DateTime $a
     * @param DateTime $b
     * @param bool     $absolute
     *
     * @return self
     */
    public static function diff(DateTime $a, DateTime $b, bool $absolute = false) : self
    {
        $microseconds = $b->microsecond() - $a->microsecond();
        
        if ($absolute) {
            $microseconds = abs($microseconds);
        }
        
        $diff = $a->toInternalDateTime()->diff($b->toInternalDateTime(), $absolute);
        
        return new static($diff, $microseconds);
    }
    
    
    /**
     * Note that if weeks and days are both specified, only days will be used and weeks will be ignored.
     *
     * @param int|null $years
     * @param int|null $months
     * @param int|null $weeks
     * @param int|null $days
     * @param int|null $hours
     * @param int|null $minutes
     * @param int|null $seconds
     * @param int|null $microseconds
     *
     * @return self
     */
    public static function fromComponents(
        int $years = null,
        int $months = null,
        int $weeks = null,
        int $days = null,
        int $hours = null,
        int $minutes = null,
        int $seconds = null,
        int $microseconds = null
    ) : self {
        if (!($years || $months || $weeks || $days || $hours || $minutes || $seconds || $microseconds)) {
            throw new \InvalidArgumentException('At least one component is required.');
        }
        
        $years        = $years ?: 0;
        $months       = $months ?: 0;
        $weeks        = $weeks ?: 0;
        $days         = $days ?: 0;
        $hours        = $hours ?: 0;
        $minutes      = $minutes ?: 0;
        $seconds      = $seconds ?: 0;
        $microseconds = $microseconds ?: 0;
        
        $specification = 'P';
        
        if ($years) {
            $specification .= $years . 'Y';
        }
        
        if ($months) {
            $specification .= $months . 'M';
        }
        
        if ($weeks) {
            $specification .= $weeks . 'W';
        }
        
        if ($days) {
            $specification .= $days . 'D';
        }
        
        if ($hours || $minutes || $seconds) {
            $specification .= 'T';
            
            if ($hours) {
                $specification .= $hours . 'H';
            }
            
            if ($minutes) {
                $specification .= $minutes . 'M';
            }
            
            if ($seconds) {
                $specification .= $seconds . 'M';
            }
        }
        
        return new static(new \DateInterval($specification), $microseconds);
    }
    
    
    /**
     * @return \DateInterval
     */
    public function toInternalDateInterval() : \DateInterval
    {
        return self::clone($this->wrapped);
    }
    
    
    /**
     * @param DateInterval $other
     *
     * @return bool
     */
    public function equals(self $other) : bool
    {
        return
            $this->wrapped->y     == $other->wrapped->y &&
            $this->wrapped->m     == $other->wrapped->m &&
            $this->wrapped->d     == $other->wrapped->d &&
            $this->wrapped->h     == $other->wrapped->h &&
            $this->wrapped->i     == $other->wrapped->i &&
            $this->wrapped->s     == $other->wrapped->s &&
            $this->wrapped->days  == $other->wrapped->days &&
            $this->microseconds   == $other->microseconds;
    }
    
    
    /**
     * In addition to the regular \DateInterval formats, this also supports %u for microseconds.
     *
     * @param string $format
     *
     * @return string
     */
    public function format(string $format) : string
    {
        // Replace microseconds first so they become literals.
        // FIXME: This needs to correctly handle literal percent signs followed by u.
        $format = str_replace('%u', $this->microseconds, $format);
        $format = $this->wrapped->format($format);
        
        return $format;
    }
    
    
    /**
     * The total number of days.
     *
     * Only available if the interval came from a diff, otherwise false will be returned.
     *
     * @return int|bool
     */
    public function totalDays()
    {
        return $this->wrapped->days;
    }


    /**
     * The total number of seconds represented by the interval, as a float.
     *
     * @return int
     */
    public function totalSeconds() : int
    {
        return $this->totalMicroseconds() / 1000000;
    }


    /**
     * The total number of milliseconds represented by the interval.
     *
     * @return int
     */
    public function totalMilliseconds() : int
    {
        return $this->totalMicroseconds() / 1000;
    }


    /**
     * The total number of microseconds represented by the interval.
     *
     * @return int
     */
    public function totalMicroseconds() : int
    {
        if (null === $this->totalMicroseconds) {
            $reference = DateTime::now();
            $end       = $reference->add($this);

            $this->totalMicroseconds = $end->timestampWithMicrosecond() - $reference->timestampWithMicrosecond();
        }

        return $this->totalMicroseconds;
    }

    
    /**
     * @return int
     */
    public function years() : int
    {
        return $this->wrapped->y;
    }
    
    
    /**
     * @return int
     */
    public function months() : int
    {
        return $this->wrapped->m;
    }
    
    
    /**
     * @return int
     */
    public function days() : int
    {
        return $this->wrapped->d;
    }
    
    
    /**
     * @return int
     */
    public function hours() : int
    {
        return $this->wrapped->h;
    }
    
    
    /**
     * @return int
     */
    public function minutes() : int
    {
        return $this->wrapped->i;
    }
    
    
    /**
     * @return int
     */
    public function seconds() : int
    {
        return $this->wrapped->s;
    }
    
    
    /**
     * @return int
     */
    public function microseconds() : int
    {
        return $this->microseconds;
    }
    
    
    /**
     * Clone does not work for DateInterval object for some reason, it leads to an error.
     *
     * "The DateInterval object has not been correctly initialized by its constructor"
     *
     * This method works around this by creating an equivalent instance manually.
     *
     * @param \DateInterval $interval
     *
     * @return \DateInterval
     */
    private static function clone(\DateInterval $interval)
    {
        $specification = $interval->format('P%yY%mM%dDT%hH%iM%sS');
        
        $clone = new \DateInterval($specification);
        
        $clone->days   = $interval->days;
        $clone->invert = $interval->invert;
        
        return $clone;
    }
}
