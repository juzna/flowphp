<?php
/**
 * Common bootstrap for all examples
 */
if (file_exists($path = __DIR__ . '/../vendor/autoload.php')) require_once $path;
elseif (file_exists($path = __DIR__ . '/../../../../vendor/autoload.php')) require_once $path;


// Init react
$eventLoop = React\EventLoop\Factory::create();

$dnsFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsFactory->createCached("8.8.8.8", $eventLoop);

$httpClientFactory = new React\HttpClient\Factory();
$httpClient = $httpClientFactory->create($eventLoop, $dnsResolver);


// Register flow() function
Flow\Flow::register(new Flow\Schedulers\HorizontalScheduler($eventLoop));
