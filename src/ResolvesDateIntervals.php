<?php

namespace Krixon\DateTime;

trait ResolvesDateIntervals
{
    /**
     * @param DateInterval|\DateInterval|string $interval
     *
     * @return \DateInterval
     */
    private static function resolveDateInterval($interval) : \DateInterval
    {
        if ($interval instanceof DateInterval) {
            return $interval->toInternalDateInterval();
        }
        
        if (is_string($interval)) {
            return new \DateInterval($interval);
        }
        
        if ($interval instanceof \DateInterval) {
            return $interval;
        }
        
        throw new \InvalidArgumentException('Interval must be a string or an instance of DateInterval.');
    }
}
