<?php

namespace Flow\Schedulers;

use Flow\FlowControl;
use Flow\PromiseWrapper;
use Flow\Result;
use React\Promise\PromiseInterface;


/**
 * Horizontal scheduler, performs components by layers
 */
class HorizontalScheduler extends BaseScheduler
{
	/** @var callable[] */
	public $onBeforeLoopCycle = [];

	/** @var callable[] */
	public $onAfterLoopCycle = [];


	public function flow(array $components)
	{
		$ret = [];
		$status = []; // id -> { component, generator, value, isFirst, parentKey, waitingFor }
		$running = []; // id -> true

		// init all components
		foreach ($components as $k => $component) {
			initAgain:
			if ($component instanceof FlowControl) $g = $component->renderFlow();
			elseif ($component instanceof \Generator) $g = $component;
			elseif ($component instanceof \Closure) {
				$component = $component();
				goto initAgain;
			} else {
				$ret[$k] = $component; // not a co-routine
				continue;
			}

			$status[$k] = [
				$component,
				$g,
				NULL,
				TRUE,
				NULL,
				NULL,
				NULL,
			];
			$running[$k] = true;
		}


		// process incrementally
		$i = count($status);
		do {
			$numWaiting = 0;

			foreach ($status as $k => list($component, $g, $v, $first, $parentKey, $waitingFor, $result)) {
				if ( ! isset($running[$k])) continue; // already finished
				if ($waitingFor) {
					if (isset($running[$waitingFor])) continue; // inner is still running
					$v = $status[$waitingFor][2];

					$status[$k][5] = NULL;
				}
				elseif ($v instanceof PromiseWrapper) { // PromiseInterface -> finished?
					if ($v->isResolved) $v = $v->data;
					else continue; // not yet finished, try next component
				}

				again:
				$v2 = $first ? $g->current() : $g->send($v);
				$status[$k][3] = $first = false;

				if ($v2 instanceof \Generator) { // inner generator created -> add to queue
					$g2 = $v2;
					$v2 = $v2->current();
					$i++;
					$status[$i] = [
						$component,
						$g2, // new generator
						$v2,
						FALSE,
						$k,
						NULL,
					];
					$running[$i] = true;

					$status[$k][5] = $i; // current is waiting for the new one
					$numWaiting++;
				}
				elseif ( ! $g->valid()) unset($running[$k]);
				elseif ($v2 instanceof PromiseInterface) {
					$status[$k][2] = $p = new PromiseWrapper($v2);
					if ($p->isResolved) {
						$v = $status[$k][2] = $p->data;
						goto again;
					} else {
						$numWaiting++;
					}
				}
				else {
					if ($v2 instanceof Result) {
						$status[$k][6] = $v2->data;
						unset($running[$k]);
						continue;
					}
					$v = $status[$k][2] = $v2; // pure value
					goto again;
				}
			}

			if ($numWaiting === 0) continue; // nothing waits, try next level
			foreach ($this->onBeforeLoopCycle as $cb) $cb($this);
			$this->eventLoop->run();
			foreach ($this->onAfterLoopCycle as $cb) $cb($this);
		} while($running);


		// collect results
		foreach ($status as $k => list($component, $g, $v, $first, $parentKey, $waitingFor, $result)) {
			if (isset($components[$k])) { // must be original component (not an inner one)
				$ret[$k] = $result;
			}
		}

		return $ret;
	}

}
