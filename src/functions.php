<?php
/**
 * Auxiliary functions for make coding nicer
 */



/**
 * Creates a return value from co-routine
 *
 * @param mixed $value
 * @return Flow\Result
 */
function result($value) {
	return new Flow\Result($value);
}


/**
 * Shortcut for Flow\Flow::run
 *
 * @param array|mixed $components
 * @return array|mixed
 */
function flow($components) {
	return Flow\Flow::run($components);
}
