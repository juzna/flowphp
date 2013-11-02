<?php

namespace Flow;


/**
 * Wrapper for return value of co-routine
 */
class Result
{
	public $data;

	public function __construct($data)
	{
		$this->data = $data;
	}

}
