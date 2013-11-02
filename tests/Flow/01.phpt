<?php
use Tester\Assert;
use React\Promise\Deferred;


require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/MockEventLoop.php';


$loop = new MockEventLoop;
$scheduler = new Flow\Schedulers\NaiveScheduler($loop);


// normal function, not a generator
$ret = $scheduler->flow([
	function() {
		return 'normal function';
	}
]);
Assert::same( [ 'normal function' ], $ret);


// several functions
$ret = $scheduler->flow([
	function() {
		return 'A';
	},
	function() {
		return 'B';
	},
]);
Assert::same( [ 'A', 'B' ], $ret);


// generator without return value
$ret = $scheduler->flow([
	function() {
		yield 'dummy';
	}
]);
Assert::same( [ NULL ], $ret);


// generator with return value
$ret = $scheduler->flow([
	function() {
		yield result('retval');
	}
]);
Assert::same( [ 'retval' ], $ret);


// yield with pure value
$ret = $scheduler->flow([
	function() {
		$x = (yield "foo");
		Assert::same("foo", $x);
	}
]);
Assert::same( [ NULL ], $ret);


// yield with pure value array
$ret = $scheduler->flow([
	function() {
		$x = (yield [ "foo", "bar" ]);
		Assert::same( [ "foo", "bar" ], $x);
	}
]);
Assert::same( [ NULL ], $ret);


// yield with already resolved promise
$loop->ticks = NULL;
$ret = $scheduler->flow([
	function() {
		$promise = new React\Promise\FulfilledPromise("foo");
		$x = (yield $promise);
		Assert::same("foo", $x);
	}
]);
Assert::same( [ NULL ], $ret);
Assert::same(NULL, $loop->ticks); // loop not ticked


// yield with promise
$loop->ticks = NULL;
$ret = $scheduler->flow([
	function() use ($loop) {
		$promise = $loop->createTickPromise("foo"); // will be resolved on tick

		$x = (yield $promise);
		Assert::same("foo", $x);
	}
]);
Assert::same( [ NULL ], $ret);
Assert::same(1, count($loop->ticks));


// yield with inner generator
$loop->ticks = NULL;
$ret = $scheduler->flow([
	function() use ($loop) {
		// inner generator
		$g = function() use ($loop) {
			$foo = (yield $loop->createTickPromise("foo")); // will be resolved on tick
			$bar = (yield $loop->createTickPromise("bar")); // will be resolved on tick
			yield result($foo);
		};

		$x = (yield $g());
		Assert::same("foo", $x);
	}
]);
Assert::same( [ NULL ], $ret);
Assert::same(2, count($loop->ticks));


// yield with rejected promise
$ret = $scheduler->flow([
	function() {
		$p = new React\Promise\RejectedPromise(new InvalidArgumentException('foo'));
		$foo = (yield $p); // shall throw

		Assert::fail("shall not reach this code");
	}
]);
Assert::true($ret[0] instanceof InvalidArgumentException);
