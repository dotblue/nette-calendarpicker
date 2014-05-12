<?php

/**
 * Copyright (c) dotBlue (http://dotblue.net)
 */

namespace DotBlue\Nette\Forms;

use Nette\DI;
use Nette\PhpGenerator;


class CalendarPickerExtension extends DI\CompilerExtension
{

	/** @var string */
	private $defaults = array(
		'method' => 'addCalendarPicker',
		'jsConverter' => array(),
	);



	public function afterCompile(PhpGenerator\ClassType $class)
	{
		parent::afterCompile($class);

		$config = $this->getConfig($this->defaults);

		$init = $class->methods['initialize'];
		$init->addBody('DotBlue\Nette\Forms\CalendarPicker::register(\'' . $config['method'] .'\');');
		if ($config['jsConverter']) {
			$init->addBody('DotBlue\Nette\Forms\CalendarPicker::$jsConverter = array_merge(DotBlue\Nette\Forms\CalendarPicker::$jsConverter, ?);', array(
				$config['jsConverter'],
			));
		}
	}

}
