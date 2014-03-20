## Flexible date (and time) picker for Nette Framework

#### Requirements

- PHP 5.3+
- [nette/nette](https://github.com/nette/mette) >= 2.1

## Installation

1.  Copy source codes from Github or using [Composer](http://getcomposer.org/):
```sh
$ composer require dotblue/nette-calendarpicker@~1.0
```

2.  Register as Configurator's extension:
```
extensions:
	calendarPicker: DotBlue\Nette\Forms\CalendarPickerExtension
```

And you're good to go :).

## Usage

You can use new method `addCalendarPicker()`:

```php
$form->addCalendarPicker('date_created', 'Date of creation:');
```
