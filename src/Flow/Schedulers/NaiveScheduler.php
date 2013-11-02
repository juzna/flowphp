<?php

namespace Flow\Schedulers;

use Flow\FlowControl;
use Flow\PromiseWrapper;
use Flow\Result;
use React\Promise\PromiseInterface;


/**
 * Naive scheduler, performs one component at a time
 */
class NaiveScheduler extends BaseScheduler
{

	public function flow(array $components)
	{
		$ret = [];

		foreach ($components as $k => $component) {
			$v = null;
			$first = true;
			if ($component instanceof FlowControl) $g = $component->renderFlow();
			elseif ($component instanceof \Generator) $g = $component;
			elseif ($component instanceof \Closure) $g = $component();
			else throw new \Exception("Invalid component given");

			do {
				$v2 = $first ? $g->current() : $g->send($v);
				$first = false;

				if (!$g->valid()) break;
				elseif ($v2 instanceof PromiseInterface) {
					$v = new PromiseWrapper($v2);
					while (!$v->isResolved) {
						$this->eventLoop->run();
					}
					$v = $v->data;
				}
				elseif ($v2 instanceof Result) $v = $v2->data;
				else $v = $v2;

			} while($g->valid());

			$ret[$k] = $v;
		}

		return $ret;
	}

}
