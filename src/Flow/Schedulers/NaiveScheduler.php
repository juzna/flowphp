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
				try {
					$v2 = $first ? $g->current() : $g->send($v);
				} catch(\Exception $e) {
					$ret[$k] = $e;
					break;
				}
				$first = false;

				a2:
				if ($v2 instanceof \Generator) {
					$v2 = static::flow([$v2])[0];
				}

				if (!$g->valid()) break;
				elseif ($v2 instanceof \Exception) {
					try {
						$v2 = $g->throw($v2);
						goto a2;
					} catch(\Exception $e) {
						$ret[$k] = $e;
						break;
					}
				}
				elseif ($v2 instanceof PromiseInterface) {
					$v = new PromiseWrapper($v2);
					while (!$v->isResolved) {
						$this->eventLoop->run();
					}
					if ($v->error) {
						try {
							$v2 = $g->throw($v->error instanceof \Exception ? $v->error : new \Exception($v->error));
							goto a2;
						} catch(\Exception $e) {
							$ret[$k] = $e;
							break;
						}
					} else {
						$v = $v->data;
					}
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
