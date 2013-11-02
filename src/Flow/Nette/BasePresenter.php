<?php

namespace Flow;

use Flow\Nette\Helpers;
use Nette;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

	public function sendResponse(Nette\Application\IResponse $response)
	{
		if ($response instanceof Nette\Application\Responses\TextResponse && $response->source instanceof Nette\Templating\ITemplate) {
			echo Helpers::renderTemplate($response->source);

		} else {
			parent::sendResponse($response);

		}
	}

}
