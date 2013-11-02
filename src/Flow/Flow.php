<?php

namespace Flow;

use React\Promise\PromiseInterface;


class Flow
{
	public static $async = FALSE;
	public static $components = [];


	public static function addComponent($component) {
		$i = count(self::$components);

		self::$components[$i] = $component;
		return "\x01$i\x02"; // magic placeholder
	}


	public static function renderTemplateSync(\Nette\Templating\Template $tpl)
	{
		self::$async = FALSE;
		return $tpl->__toString();
	}


	public static function renderTemplateAsync(\Nette\Templating\Template $tpl)
	{
		self::$async = TRUE;

		$partial = $tpl->__toString(); // render, but component will provide only placeholders

		$ret = self::flowAuto(self::$components);

		$html = preg_replace_callback('/\x01(\d+)\x02/', function($m) use ($ret) {
			return $ret[$m[1]];
		}, $partial);

		return $html;
	}


	public static function components()
	{
		return self::flowAuto(self::$components);
	}


	public static function flowAuto($array)
	{
		return self::flowComponentsHorizontal($array);
	}


	public static function flowComponentsNaive(array $components) {
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
						\Nette\Environment::getService('eventLoop')->run();
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


	public static function flowComponentsHorizontal(array $components) {
		$ret = [];
		$status = []; // id -> { component, generator, value, isFirst, parentKey, waitingFor }
		$running = []; // id -> true


		// init all components
		foreach ($components as $k => $component) {
			if ($component instanceof FlowControl) $g = $component->renderFlow();
			elseif ($component instanceof \Generator) $g = $component;
			elseif ($component instanceof \Closure) $g = $component();
			else throw new \Exception("Invalid component given");

			$status[$k] = [
				$component,
				$g,
				NULL,
				TRUE,
				NULL,
				NULL,
			];
			$running[$k] = true;
		}


		// process incrementally
		$i = count($components);
		do {
			foreach ($status as $k => list($component, $g, $v, $first, $parentKey, $waitingFor)) {
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
				}
				elseif ( ! $g->valid()) unset($running[$k]);
				elseif ($v2 instanceof PromiseInterface) $status[$k][2] = new PromiseWrapper($v2);
				else {
					if ($v2 instanceof Result) {
						$status[$k][2] = $v2->data;
						unset($running[$k]);
						continue;
					}
					$v = $status[$k][2] = $v2; // pure value
					goto again;
				}
			}

//			echo "round done, sending bulk requests and waiting for network\n";
//			Scheduler::sendRequests();
			echo "waiting for react\n";
			\Nette\Environment::getService('eventLoop')->run();
			echo "\n\n";
		} while($running);


		// collect results
		foreach ($status as $k => list($component, $g, $v, $first)) {
			if (isset($components[$k])) { // must be original component (not an inner one)
				$ret[$k] = $v;
			}
		}

		return $ret;
	}

}
