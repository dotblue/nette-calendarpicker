<?php

/**
 * Copyright (c) dotBlue (http://dotblue.net)
 */

namespace DotBlue\Nette\Forms;

use DateTime;
use InvalidArgumentException;
use Nette;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Container;
use Nette\Forms\Form;


class CalendarPicker extends BaseControl
{

	const DEFAULT_MASK_DATE = 'j. n. Y';
	const DEFAULT_MASK_DATETIME = 'j. n. Y H:i:s';
	const DEFAULT_INVALID_DATE_MESSAGE = 'Invalid date';
	const DEFAULT_INVALID_TIME_MESSAGE = 'Invalid time';

	public static $jsConverter = array(
		'j' => 'd',
		'd' => 'dd',
		'D' => 'D',
		'l' => 'DD',
		'z' => 'o',
		// '' => 'oo', unsupported in PHP
		'n' => 'm',
		'm' => 'mm',
		// 'M' => 'M',
		'F' => 'MM',
		'y' => 'yy',
		'Y' => 'yyyy',
		'U' => '@',
		// time
		'a' => 'p',
		'A' => 'P',
		// '' => 's', unsupported in PHP
		's' => 'ss',
		// '' => 'i', unsupported in PHP
		'i' => 'ii',
		'G' => 'h',
		'H' => 'hh',
		'g' => 'H',
		'h' => 'HH',
	); // compatible with http://www.malot.fr/bootstrap-datetimepicker/

	/** @var bool */
	private $useTime = FALSE;

	/** @var int */
	private $year;

	/** @var int */
	private $month;

	/** @var int */
	private $day;

	/** @var int */
	private $hour;

	/** @var int */
	private $minute;

	/** @var int */
	private $second;

	/** @var string */
	private $phpMask;

	/** @var string */
	private $jsMask;

	/** @var callable */
	private $parseDateCallback;

	/** @var callable */
	private $formatDateCallback;

	/** @var string */
	private $invalidDateMessage;



	/**
	 * @param  string|NULL
	 * @param  string
	 */
	public function __construct($label = NULL, $invalidDateMessage = self::DEFAULT_INVALID_DATE_MESSAGE)
	{
		parent::__construct($label);
		$this->invalidDateMessage = $invalidDateMessage;
		$this->setMask($this->useTime ? self::DEFAULT_MASK_DATETIME : self::DEFAULT_MASK_DATE);
		$this->addCondition(Form::FILLED)
			->addRule(array($this, 'validateDate'), $this->invalidDateMessage);

		$this->parseDateCallback = array($this, 'parseDate');
		$this->formatDateCallback = array($this, 'formatDate');
	}



	/**
	 * Sets custom parser of date string.
	 *
	 * @param  callable
	 * @return CalendarPicker provides a fluent interface
	 */
	public function setParser($callback)
	{
		$this->parseDateCallback = $callback;
		return $this;
	}



	/**
	 * Sets custom formatter of date object.
	 *
	 * @param  callable
	 * @return CalendarPicker provides a fluent interface
	 */
	public function setFormatter($callback)
	{
		$this->formatDateCallback = $callback;
		return $this;
	}



	/**
	 * Adds UI for setting time of day.
	 * Don't forget to set correct mask if you don't use default one.
	 *
	 * @param  string
	 * @return CalendarPicker provides a fluent interface
	 */
	public function useTime($invalidTimeMessage = self::DEFAULT_INVALID_TIME_MESSAGE)
	{
		$this->useTime = TRUE;
		if ($this->phpMask === self::DEFAULT_MASK_DATE) {
			$this->setMask(self::DEFAULT_MASK_DATETIME);
		}
		$this->addCondition(Form::FILLED)
			->addRule(array($this, 'validateTime'), $invalidTimeMessage);
		return $this;
	}



	/**
	 * Sets mask used by PHP for initial rendering.
	 *
	 * @param  string
	 * @return CalendarPicker provides a fluent interface
	 */
	public function setMask($phpMask)
	{
		$this->phpMask = $phpMask;
		$this->jsMask = $this->createJsMask($phpMask);
		return $this;
	}



	public function loadHttpData()
	{
		$value = Nette\DateTime::createFromFormat(
			$this->phpMask,
			$this->getHttpData(Form::DATA_LINE)
		);
		if ($value !== FALSE) {
			$this->year = $value->format('Y');
			$this->month = $value->format('n');
			$this->day = $value->format('j');
			if ($this->useTime) {
				$this->hour = $value->format('G');
				$this->minute = (int) $value->format('i');
				$this->second = (int) $value->format('s');
			}
		}
	}



	public function setValue($value)
	{
		if ($value) {
			if (is_string($value)) {
				$date = call_user_func($this->parseDateCallback, $this->phpMask, $value);
			} else {
				$date = Nette\DateTime::from($value);
			}

			if (!$date instanceof DateTime) {
				throw new InvalidArgumentException("Invalid input for calendar picker: '$value'");
			}

			$this->year = $date->format('Y');
			$this->month = $date->format('n');
			$this->day = $date->format('j');
			if ($this->useTime) {
				$this->hour = $date->format('G');
				$this->minute = (int) $date->format('i');
				$this->second = (int) $date->format('s');
			}
		} else {
			$this->year = $this->month = $this->day = NULL;
			if ($this->useTime) {
				$this->hour = $this->minute = $this->second = NULL;
			}
		}
	}



	public function getValue()
	{
		if ($this->validateDate($this)) {
			$date = date_create()->setDate($this->year, $this->month, $this->day);
			if ($this->useTime) {
				if ($this->validateTime($this)) {
					$date->setTime($this->hour, $this->minute, $this->second);
				} else {
					return;
				}
			}
			return $date;
		}
	}



	public function getControl()
	{
		$el = parent::getControl();
		$el->type('date');
		if ($value = $this->getValue()) {
			$value = call_user_func($this->formatDateCallback, $this->phpMask, $value);
			$el->value($value);
		}
		$el->data('nette-datetime', $this->jsMask);
		return $el;
	}



	/**
	 * @internal
	 */
	public function validateDate(CalendarPicker $picker)
	{
		return checkdate($picker->getMonth(), $picker->getDay(), $picker->getYear());
	}



	/**
	 * @internal
	 */
	public function validateTime(CalendarPicker $picker)
	{
		$hour = $picker->getHour();
		if ($hour < 0 || $hour > 23 || !is_numeric($hour)) {
			return FALSE;
		}
		$minute = $picker->getMinute();
		if ($minute < 0 || $minute > 59 || !is_numeric($minute)) {
			return FALSE;
		}
		$second = $picker->getSecond();
		if ($second < 0 || $second > 59 || !is_numeric($second)) {
			return FALSE;
		}
		return TRUE;
	}



	/**
	 * @internal
	 */
	public function getYear()
	{
		return $this->year;
	}



	/**
	 * @internal
	 */
	public function getMonth()
	{
		return $this->month;
	}



	/**
	 * @internal
	 */
	public function getDay()
	{
		return $this->day;
	}



	/**
	 * @internal
	 */
	public function getHour()
	{
		return $this->hour;
	}



	/**
	 * @internal
	 */
	public function getMinute()
	{
		return $this->minute;
	}



	/**
	 * @internal
	 */
	public function getSecond()
	{
		return $this->second;
	}



	/**
	 * @internal
	 */
	public function parseDate($phpMask, $value)
	{
		return Nette\DateTime::createFromFormat($phpMask, $value);
	}



	/**
	 * @internal
	 */
	public function formatDate($phpMask, $value)
	{
		return $value->format($phpMask);
	}



	private function createJsMask($phpMask)
	{
		return strtr($phpMask, static::$jsConverter);
	}



	public static function register($methodName = 'addCalendarPicker')
	{
		Container::extensionMethod($methodName, function (Container $_this, $name, $label = NULL, $invalidDateMessage = CalendarPicker::DEFAULT_INVALID_DATE_MESSAGE) {
			return $_this[$name] = new CalendarPicker($label, $invalidDateMessage);
		});
	}

}
