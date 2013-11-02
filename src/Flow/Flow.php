<?php

namespace Flow;


/**
 * Static helper for schedulers
 */
class Flow
{
	const PASS = "\x91\x33\x65"; // magic

	/** @var IScheduler */
	private static $scheduler;



	public static function register(IScheduler $scheduler)
	{
		if (self::$scheduler) throw new \Exception("Scheduler already registered");
		self::$scheduler = $scheduler;
	}


	/**
	 * @param array|callable $components
	 * @return array|mixed
	 */
	public static function run($components)
	{
		if ( ! self::$scheduler) throw new \Exception("Scheduler not yet registered");

		// le wrap
		if (is_array($components)) {
			$retArray = TRUE;

		} else {
			$retArray = FALSE;
			$components = [ $components ];
		}

		// le flow
		$ret = self::$scheduler->flow($components);

		if ( ! $retArray) {
			$ret = reset($ret);
			if ($ret instanceof \Exception) throw $ret;
		}

		return $ret;
	}


	public static function add($task)
	{
		self::$scheduler->add($task);
	}

}
