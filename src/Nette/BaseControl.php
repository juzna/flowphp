<?php

namespace Flow;

/**
 * Common ancestor for components
 */
abstract class BaseControl extends \Nette\Application\UI\Control implements FlowControl
{

	public function render()
	{
		if (Flow::$async) {
			echo Flow::addComponent($this); // output placeholder
		} else {
			echo Flow::flowAuto([$this])[0]; // eager eval
		}
	}

}
