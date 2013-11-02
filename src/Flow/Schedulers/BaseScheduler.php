<?php

namespace Flow\Schedulers;

use Flow;
use React\EventLoop\LoopInterface;


/**
 * Common ancestor for React based schedulers
 */
abstract class BaseScheduler implements Flow\IScheduler
{
	/** @var LoopInterface */
	protected $eventLoop;


	public function __construct(LoopInterface $loop)
	{
		$this->eventLoop = $loop;
	}

}
