## Flow
**Cooperative Multitasking framework in PHP**

**Version**: proof of concept


More about *cooperative multitasking* in my [blog post on gist](https://gist.github.com/juzna/7194037).

Inspired by [flow](https://github.com/kaja47/flow) by K47.



## Applications
These sample applications are using Flow framework:
 - [GitHub activity](https://github.com/juzna/nette-sandbox-flow) - example of integration with Nette Framework
 - [Selenium 2 WebDriver](https://github.com/juzna/php-webdriver) - non-blocking selenium driver
 - your app can be here, email me



## Usage
Get inspired by [examples](https://github.com/juzna/flowphp/tree/master/examples) or applications which use *Flow*.


The best way to install *Flow framework* into your project is by using [Composer](http://getcomposer.org/):
```sh
$ composer require juzna/flowphp
$ composer update
```

You may also want to add [React](http://reactphp.org), which works well with *promises* and *Flow framework*.
But you better get a [forked version](https://github.com/juzna/react), which integrates better with *Flow*.

Add this towards the end of your `composer.json`:
```json
{
  ...
	"repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/juzna/react"
		}
	]
}
```
