<?php


/**
 * Dummy event loop, for use in tests
 */
class MockEventLoop implements React\EventLoop\LoopInterface
{

	/** @var bool Dump debug messages to console? */
	public static $debug = TRUE;

	/** @var float[] Time when tick happened */
	public $ticks;

	/** @var callable[] Called on tick */
	public $onTick;


	public function addReadStream($stream, $listener)
	{
		// TODO: Implement addReadStream() method.
	}


	public function addWriteStream($stream, $listener)
	{
		// TODO: Implement addWriteStream() method.
	}


	public function removeReadStream($stream)
	{
		// TODO: Implement removeReadStream() method.
	}


	public function removeWriteStream($stream)
	{
		// TODO: Implement removeWriteStream() method.
	}


	public function removeStream($stream)
	{
		// TODO: Implement removeStream() method.
	}


	public function addTimer($interval, $callback)
	{
		// TODO: Implement addTimer() method.
	}


	public function addPeriodicTimer($interval, $callback)
	{
		// TODO: Implement addPeriodicTimer() method.
	}


	public function cancelTimer(\React\EventLoop\Timer\TimerInterface $timer)
	{
		// TODO: Implement cancelTimer() method.
	}


	public function isTimerActive(\React\EventLoop\Timer\TimerInterface $timer)
	{
		// TODO: Implement isTimerActive() method.
	}


	public function tick($inLoop = FALSE)
	{
		if (self::$debug) echo $inLoop ? "run" : "tick", "\n";
		if (count($this->ticks) > 100) throw new \Exception("Almost infinite loop detected");

		$this->ticks[] = microtime(TRUE);

		if ($this->onTick) {
			foreach ($this->onTick as $cb) $cb();
		}
	}


	public function run()
	{
		$this->tick(TRUE);
	}


	public function stop()
	{
		// TODO: Implement stop() method.
	}


	/********************** testing methods **********************/

	public function createTickPromise($value = NULL, $numTicks = 1)
	{
		$d = new React\Promise\Deferred;
		$this->onTick[] = function() use ($value, &$numTicks, $d) {
			if (--$numTicks === 0) $d->resolve($value);
		};

		return $d;
	}

}
