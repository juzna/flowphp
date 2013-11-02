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
			$ret[$k] = NULL;

			$v = null;
			$first = true;
			again:
			if ($component instanceof FlowControl) $g = $component->renderFlow();
			elseif ($component instanceof \Generator) $g = $component;
			elseif ($component instanceof \Closure) {
				$component = $component();
				goto again;

			} else {
				$ret[$k] = $component; // not a co-routine
				continue;
			}

			do {
				$v2 = $first ? $g->current() : $g->send($v);
				$first = false;

				if ($v2 instanceof \Generator) $v2 = static::flow([$v2])[0];

				if (!$g->valid()) break;
				elseif ($v2 instanceof PromiseInterface) {
					$v = new PromiseWrapper($v2);
					while (!$v->isResolved) {
						$this->eventLoop->run();
					}
					$v = $v->data;
				}
				elseif ($v2 instanceof Result) {
					$ret[$k] = $v2->data;
					break;
				}
				else $v = $v2;

			} while($g->valid());
		}

		return $ret;
	}

}
