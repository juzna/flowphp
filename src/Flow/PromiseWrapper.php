<?php

namespace Flow;

use React;

/**
 * Hold the value of promise, when it gets resolved
 */
class PromiseWrapper
{
	/** @var React\Promise\PromiseInterface */
	public $promise;

	/** @var bool */
	public $isResolved = FALSE;

	/** @var mixed */
	public $data;

	/** @var \Exception|string */
	public $error;


	public function __construct(React\Promise\PromiseInterface $promise)
	{
		$promise->then(
			function($data) {
				$this->isResolved = TRUE;
				$this->data = $data;
			},
			function($err) {
				$this->isResolved = TRUE;
				$this->error = $err;
			}
		);

		$this->promise = $promise;
	}
}
