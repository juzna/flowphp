<?php
/**
 * Example of cooperative multitasking - components do http request to GitHub to determine last projects of great developers.
 */
require_once __DIR__ . '/bootstrap.php';


/**
 * Component definition (generator)
 *
 * Because it contains yield, it returns a new instance of Generator when called.
 */
function gitHubContribution($name) {
	global $httpClient; // ugly hack, DO NOT try this at home

	list($data) = (yield $httpClient->get("https://github.com/$name.json"));
	$events = json_decode($data, JSON_OBJECT_AS_ARRAY);
	if ( ! isset($events[0])) {
		yield result("No info");
	}

	$event = $events[0];
	$composerUrl = str_replace('https://', 'https://raw.', $event['repository']['url']) . '/master/composer.json';
	list($composerData) = (yield $httpClient->get($composerUrl));

	if ($composer = json_decode($composerData, JSON_OBJECT_AS_ARRAY)) {
		yield result("Last change to composer project $composer[name]");

	} else {
		yield result("Last change to github repo {$event['repository']['url']}");

	}
}



$names = [
	'juzna',
	'hosiplan',
	'dg',
	'kaja47',
	'lopo',
	'janmarek',
	'jantvrdik',
	'hrach',
];


// try blocking version
$t = microtime(TRUE);
$scheduler = new Flow\Schedulers\NaiveScheduler($eventLoop);
print_r($scheduler->flow(array_map(function($name) { return gitHubContribution($name); }, array_combine($names, $names))));
var_dump(microtime(TRUE) - $t);


// try cooperative version
$t = microtime(TRUE);
$scheduler = new Flow\Schedulers\HorizontalScheduler($eventLoop);
print_r($scheduler->flow(array_map(function($name) { return gitHubContribution($name); }, array_combine($names, $names))));
var_dump(microtime(TRUE) - $t);
