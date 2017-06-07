# cakephp-cache plugin for CakePHP
This plugin allows you set rules in order to cache Cake's responses using your favourite cache engine/settings from app.php. Then Use Ngnix or Apache modules to serve Pre rendered responses. Tata! 

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

	composer require awallef/cakephp-cache

Load it in your config/boostrap.php

	Plugin::load('Trois/Cache');

## Cache Settings
in config folder create a cache.php file with as exemple:

	<?php
	return [
  		'Trois.cache.settings' => [
    		'default' => 'default', // default cache config to use if not set in rules...
  		],
		'Trois.cache.rules' => [

			// cache request
			[
			  'cache' => 'html', // default: 'default', can be a fct($request)
			  'skip' => false, // default: false, can be a fct($request)
			  'clear' => false, // default: false, can be a fct($request)
			  'compress' => true, // default: false, can be a fct($request)
			  //'key' => 'whatEver',// default is fct($request) => return $request->here()
			  'method' => ['GET'],
			  'code' => '200', // must be set or '*' !!!!!
			  'prefix' => '*',
			  'plugin' => '*',
			  'controller' => '*',
			  'action' => '*',
			  'extension' => '*'
			],

			// clear request
			[
			  'cache' => 'html', // default: 'default'
			  'skip' => false, // default: false
			  'clear' => true, // default: false,
			  'key' => '*',
			  'method' => ['POST','PUT','DELETE'],
			  'code' => ['200','201','202'],
			  'prefix' => '*',
			  'plugin' => '*',
			  'controller' => ['Users','Pages'],
			  'action' => '*',
			  'extension' => '*'
			],
	  	]
	];

## Cache as your last middleware
in your src/Application.php file add the middleware as last chain block.

	<?php
	namespace App;
	...	
	use Trois\Cache\Middleware\ResponseCacheMiddleware;
	
	class Application extends BaseApplication
	{
	    public function middleware($middleware)
	    {
	        $middleware
				...
	            // Apply Response caching
	            ->add(ResponseCacheMiddleware::class);
	
	        return $middleware;
	    }
	}

## Redis caching
This plugin provides a very little bit different redis engine based on cakephp's RedisEngine.
differences are:

- Engine config comes with a bool 'serialize' option ( default is true )
- Read and wirte fct use config 'serialize' option
- Keys are stored/read/deleted in order to uses : and :* redis skills!

Configure the engine in app.php like follow:

	'Cache' => [ 
	    ...
	    'html' => [
	      'className' => 'Trois/Cache.ExtendedRedis',
	      'prefix' => 'www.your-site.com:',
	      'duration' => '+24 hours',
	      'serialize' => false
	    ],
	    ...
	]
	
Then use it in config/cache.php

	'Trois.cache.settings' => [
		'default' => 'html', // as default....
	],
	'Trois.cache.rules' => [

		// cache request
		[
		  'cache' => 'html', // or in a specific call
		  ...
		],