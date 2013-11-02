<?php

namespace Flow\Nette;

use Flow\Flow;
use Nette;
use Nette\Templating\Template;


/**
 * Auxiliary methods used by the other classes
 */
class Helpers
{

	public static $async = FALSE;
	public static $components = [];


	public static function addComponent($component) {
		$i = count(self::$components);

		self::$components[$i] = $component;
		return "\x01$i\x02"; // magic placeholder
	}


	public static function renderTemplate(Template $tpl)
	{
		if (self::$async) {
			return self::renderTemplateSync($tpl);
		} else {
			return self::renderTemplateAsync($tpl);
		}
	}


	private static function renderTemplateSync(Template $tpl)
	{
		return $tpl->__toString();
	}


	private static function renderTemplateAsync(Template $tpl)
	{
		$partial = $tpl->__toString(); // render, but component will provide only placeholders

		$ret = Flow::run(self::$components);

		$html = preg_replace_callback('/\x01(\d+)\x02/', function($m) use ($ret) {
			return $ret[$m[1]];
		}, $partial);

		return $html;
	}

}
