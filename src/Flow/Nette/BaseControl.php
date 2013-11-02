<?php

namespace Flow;

use Flow\Nette\Helpers;
use Nette;


/**
 * Common ancestor for components
 */
abstract class BaseControl extends Nette\Application\UI\Control implements FlowControl
{

	public function render()
	{
		if (Helpers::$async) {
			echo Helpers::addComponent($this); // output placeholder

		} else {
			echo Flow::run([$this])[0]; // eager eval

		}
	}

}
