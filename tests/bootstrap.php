<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 25-1-2017
 * Time: 10:36
 */


// echo "AAA";
// include_once( realpath( __DIR__ . '/../vendor/' ) . DIRECTORY_SEPARATOR . 'autoload.php' );
//include_once __DIR__.'/../vendor/autoload.php';

//$classLoader = new \Composer\Autoload\ClassLoader();
//$classLoader->addPsr4("Voetbal\\Tests\\", __DIR__, true);
//$classLoader->register();

// include_once( realpath( __DIR__ . '/../vendor/composer/' ) . DIRECTORY_SEPARATOR . 'autoload_psr4.php' );
// var_dump( realpath( __DIR__ . '/../vendor/composer/' ) . DIRECTORY_SEPARATOR . 'autoload_psr4.php' );

class Test1 implements JsonSerializable
{
	private $name;

	public function __construct( $name ) {
		$this->name = $name;
	}

	public function jsonSerialize() {
		return $this->name;
	}
}

$test1 = new Test1("testjecdk");
var_dump( json_encode( $test1 ));
echo PHP_EOL;