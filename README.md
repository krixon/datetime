datetime
========

[![Build Status](https://travis-ci.org/krixon/datetime.svg?branch=master)](https://travis-ci.org/krixon/datetime)
[![Coverage Status](https://coveralls.io/repos/github/krixon/datetime/badge.svg?branch=master)](https://coveralls.io/github/krixon/datetime?branch=master)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/49959a02-8e20-48fb-a12d-16f2dec82d7b/big.png)](https://insight.sensiolabs.com/projects/49959a02-8e20-48fb-a12d-16f2dec82d7b)

PHP7 date/time library.

# Prerequisites

- PHP 7.0+

# Installation


## Install via composer

To install datetime with Composer, run the following command:

```sh
$ composer require krixon/datetime
```

You can see this library on [Packagist](https://packagist.org/packages/krixon/datetime).

## Install from source

```sh
# HTTP
$ git clone https://github.com/krixon/datetime.git
# SSH
$ git clone git@github.com:krixon/datetime.git
```

# Introduction

This library is a layer on top of PHP's built-in date and time classes which provides additional functionality
and improvements such as microsecond precision and immutability (without the inconsistencies between `\DateTime`,
`\DateTimeImmutable` and `DateTimeInterface`).

## Creating Dates

There are various ways to create a new `DateTime` instance.

Using the current time and default timezone:
```php
// These objects all represent the current time.
$date = DateTime::now();
$date = DateTime::create();
$date = new DateTime();
```

Using a UNIX timestamp:
```php
// Standard (second) precision.
DateTime::fromTimestamp(1499789008)->format('Y-m-d H:i:s.u');
// 2017-07-11 16:03:28.000000

// Millisecond precision.
DateTime::fromTimestampWithMilliseconds(1499789008123)->format('Y-m-d H:i:s.u');
// 2017-07-11 16:03:28.123000

// Microsecond precision.
DateTime::fromTimestampWithMicroseconds(1499789008123456)->format('Y-m-d H:i:s.u');
// 2017-07-11 16:03:28.123456
```

Parsing a string using a specified format:
```php
$date = DateTime::fromFormat('Y-m-d H:i:s.u', '2017-07-11 16:03:28.123456');
```

Parsing a string containing any [supported date and time format](http://php.net/manual/en/datetime.formats.php):
```php
$date = DateTime::create('yesterday');
$date = DateTime::create('1 month ago');
$date = DateTime::create('first day of January 2008');
$date = DateTime::create('+5 weeks');
$date = DateTime::create('Monday next week');
// etc
```

Using an existing built-in `\DateTime` instance:
```php
$date = DateTime::fromInternalDateTime(new \DateTime());
```

Using an existing `\IntlCalendar` instance:
```php
$calendar = \IntlCalendar::createInstance();
$calendar->setTime(1499789008123);
$date = DateTime::fromIntlCalendar($calendar);
```

## Modifying Dates

All `DateTime` instances are immutable. However methods are provided for creating new instances with modifications
applied.

Adjusting the date:
```php
$date = DateTime::create('21st March 2017 09:45:00');

$date->withDateAt(2016, 09, 15); // 2016-09-15 09:45:00

// Any components not specified will not be changed.
$date->withDateAt(null, null, 15); // 2017-01-15 09:45:00

// There are also methods for setting the components individually.
$date->withYear(1981);           // 1981-03-21 09:45:00
$date->withMonth(DateTime::JAN); // 2017-01-21 09:45:00
$date->withDay(15);              // 2017-03-15 09:45:00

// Convenience methods for common date adjustments.
$date->withDateAtStartOfYear();                       // 2017-01-01 00:00:00
$date->withDateAtStartOfMonth();                      // 2017-03-01 00:00:00
$date->withDateAtEndOfMonth();                        // 2017-03-31 00:00:00
$date->withDateAtDayOfWeekInMonth(DateTime::TUE, 4);  // 2017-03-28 00:00:00 (4th Tuesday in March 2017)
$date->withDateAtDayOfWeekInMonth(DateTime::MON, -2); // 2017-03-20 00:00:00 (Penultimate Tuesday in March 2017)
$date->withDateAtStartOfWeek('en_GB');                // 2017-03-20 00:00:00 (Monday, start of the week of 21st Match 2017 in Great Britain).
$date->withDateAtStartOfWeek('en_US');                // 2017-03-19 00:00:00 (Sunday, start of the week of 21st Match 2017 in USA).
```

If you are making many changes to a `DateTime` without needing the intermediate objects, you can use the
`DateTimeCalculator` class. This supports all of the operations you can do on a `DateTime` object itself but without
the overhead of creating new objects which are then thrown away.

For example, imagine you want to add an interval to a base date a number of times, but you are only interested in
the final result. While you could call `$date = $date->add('PT1D')` repeatedly, a more efficient method would be:

```php
$calculator = DateTime::create('2017-01-01')->calculator();

for ($i = 0; $i < 50; $i++) {
    $calculator->addInterval('PT1D');
}

$date = $calculator->result(); // 2017-02-19
```

Of course this is a contrived example and in reality you would just call `$date = $date->add('PT50D')`, but there
are many arithmetic operations you can perform with the calculator which cannot necessarily be achieved as efficiently
using just the `DateTime` API.
