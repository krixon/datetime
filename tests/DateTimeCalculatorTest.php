<?php

namespace Krixon\DateTime\Test;

use Krixon\DateTime\DateTime;
use Krixon\DateTime\DateTimeCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Krixon\DateTime\DateTimeCalculator
 * @covers ::<protected>
 * @covers ::<private>
 */
class DateTimeCalculatorTest extends TestCase
{
    /**
     * @dataProvider addProvider
     * @covers ::add
     *
     * @param string      $base
     * @param int         $field
     * @param int         $amount
     * @param string      $expected
     * @param string|null $locale
     */
    public function testAdd(string $base, int $field, int $amount, string $expected, string $locale = null)
    {
        $calculator = DateTimeCalculator::basedOn(DateTime::create($base), $locale);
        
        $calculator->add($field, $amount);
        
        self::assertCalculationResult($expected, $calculator);
    }
    
    
    public function addProvider() : array
    {
        return [
            ['2015-01-01 00:00:00', DateTimeCalculator::YEAR, 1, '2016-01-01 00:00:00'],
            ['2015-01-01 00:00:00', DateTimeCalculator::YEAR, 5, '2020-01-01 00:00:00'],
            ['2015-01-01 00:00:00', DateTimeCalculator::YEAR, -5, '2010-01-01 00:00:00'],
            ['2015-01-01 00:00:00', DateTimeCalculator::DAY_OF_MONTH, -5, '2014-12-27 00:00:00'],
        ];
    }
    
    
    private static function assertCalculationResult(string $expected, DateTimeCalculator $calculator)
    {
        $expected = DateTime::create($expected);
        $result   = $calculator->result();
        
        self::assertTrue(
            $result->equals($expected),
            sprintf("Failed asserting that $result equals expected $expected.")
        );
    }
}
