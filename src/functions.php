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
