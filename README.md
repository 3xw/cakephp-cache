# cakephp-cache plugin for CakePHP
This plugin allows you set rules in order to cache Cake's responses using your favourite cache engine/settings from app.php. Then Use Ngnix or Apache modules to serve Pre rendered responses. Tata!

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

	composer require awallef/cakephp-cache:dev-master

Load it in your config/boostrap.php

	Plugin::load('Awallef/Cache');

## How it works
A middleware creates cache response as you want using app.php cache engines. Then you have two options to retrieve:

- A component retrieve the cached response before your action is called, but after auth and whatever you need.
- You retrieve the cache response server side with Nginx or any other binary

## Cache Settings
With a config file you can tell your app when to cache and when to clear cache.
In config folder create a cache.php file with as exemple:

	<?php
	return [
  		'Awallef.cache.settings' => [
    		'default' => 'default', // default cache config to use if not set in rules...
  		],
		'Awallef.cache.rules' => [

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
			  'key' => '*', // * => Cache::clear(false, cache) (Will clear all keys), 'whatEver' => Cache::delete('whatEver', cache), null => Cache::delete($request->here(), cache)
			  'method' => ['POST','PUT','DELETE'],
			  'code' => ['200','201','202','302'], // 302 is often triggered by cakephp in case of success crud operation...
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
This will create or delete view renders as cache ( html/json /etc...)

	<?php
	namespace App;
	...
	use Awallef\Cache\Middleware\ResponseCacheMiddleware;

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

## Retrieve cache via ActionCacheComponent
in your AppController load the component AFTER Auth!!

	$this->loadComponent('Awallef/Cache.ActionCache');
	// normal settings

	// OR

	$this->loadComponent('Awallef/Cache.ActionCache',['skip_debug' => false]);
	// skip_debug default is true
	// so here it means cache is deliver even if you are in debug mode....

## Retrieve cache via Nginx
First install nginx redis extension. Then set your cache config to store in redis. You can use my plugin...

	composer require awallef/cakephp-redis


Configure the engine in app.php like follow:

	'Cache' => [
		...
		'redis' => [
			'className' => 'Awallef/Redis.Redis',
			'prefix' => 'hello.com:',
			'duration' => '+24 hours',
			'serialize' => true
		],
		...
	]

Configure cache.php like follow:

	return [
			'Awallef.cache.settings' => [
				'default' => 'redis', // default cache config to use if not set in rules...
			],
		'Awallef.cache.rules' => [

			// cache request
			[
				'skip' => false, // default: false, can be a fct($request)
				'clear' => false, // default: false, can be a fct($request)
				'compress' => true, // default: true, can be a fct($request)
				//'key' => 'whatEver',// default is fct($request) => return $request->here()
				'method' => ['GET'],
				'code' => '200', // must be set or '*' !!!!!
				'prefix' => '*',
				'plugin' => '*',
				'controller' => '*',
				'action' => '*',
				'extension' => '*'
			],
		]
	];

Configure Nginx too:

	map $http_accept $hello_com_response_header {
		default   "text/html; charset=UTF-8";
		"~*json"  "application/json; charset=UTF-8";
	}
	server {
		listen 443;
		server_name hello.com;

		ssl on;
		...

		# redis key
		set $redis_key  "hello.com:$request_uri";
		if ($args) {
			set $redis_key  "hello.com:$request_uri?$args";
		}

		location / {
			redis_pass 127.0.0.1:6379;
			error_page 404 405 502 504 = @fallback;
			more_set_headers "Content-Type: $hello_com_response_header";
		}

		#default cake handling
		location @fallback {
			try_files $uri $uri/ /index.php?$args;
		}

		location ~ \.php$ {
			try_files $uri =404;
			include /etc/nginx/fastcgi_params;
			fastcgi_intercept_errors on;
			fastcgi_pass unix:/var/run/php/php7.1-fpm.sock;
			fastcgi_index   index.php;
			fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
		}

	}

## Tip
You've better to use different cache settings for each server side retrieve and action cache component retrieve... Otherwise Nginx/Server will serve action cache without checking any of your ACL rules!

## more
upgrades coming
