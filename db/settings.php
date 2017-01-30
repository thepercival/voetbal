<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 14:15
 */

$dotenv = new \Dotenv\Dotenv( __DIR__ . '/../' );
$dotenv->load();

return [
	'settings' => [
		'environment' => getenv('ENVIRONMENT'),
		'displayErrorDetails' => ( getenv('ENVIRONMENT') === "development" ),
		'addContentLengthHeader' => false, // Allow the web server to send the content-length header

		// Doctrine settings
		'doctrine' => [
			'meta' => [
				'entity_path' => ['db/yml-mapping'],
				'auto_generate_proxies' => ( getenv('ENVIRONMENT') === "development" ),
				'proxy_dir' =>  __DIR__.'/../cache/proxies',
				'cache' => null,
			],
			'connection' => [
				'driver'   => getenv('DB_DRIVER'),
				'host'     => getenv('DB_HOST'),
				'dbname'   => getenv('DB_NAME'),
				'user'     => getenv('DB_USERNAME'),
				'password' => getenv('DB_PASSWORD'),
			],
			'serializer' => array(
				'enabled' => true,
			),
		],

	],
];