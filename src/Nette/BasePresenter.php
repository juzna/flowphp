<?php

namespace Flow;

use Nette;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

	public function sendResponse(\Nette\Application\IResponse $response)
	{
		if ($response instanceof \Nette\Application\Responses\TextResponse && $response->source instanceof Nette\Templating\ITemplate) {
			echo Flow::renderTemplateAsync($response->source);

		} else {
			parent::sendResponse($response);

		}
	}

}
