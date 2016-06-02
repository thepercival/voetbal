<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 9-2-2016
 * Time: 09:12
 */


namespace Voetbal;

require_once __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Europe/Amsterdam');

use Carbon\Carbon;
use League\Period\Period;
use League\Tactician\Handler\MethodNameInflector\MethodNameInflector;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Plugins\LockingMiddleware;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class Inflector implements MethodNameInflector
{
    public function inflect($command, $commandHandler)
    {
        return 'handle';
    }
}

class Service
{
    protected static $entityManager;

    public static function getEntityManager()
    {
        if ( static::$entityManager !== null )
            return static::$entityManager;

        $isDevMode = true;
        $config = Setup::createYAMLMetadataConfiguration(array(realpath( __DIR__ ."/../db" )), $isDevMode);
        $arrConfig = parse_ini_file( __DIR__ . "/../config/voetbal.ini", true );
        return ( static::$entityManager = EntityManager::create( $arrConfig["database"], $config) );
    }

    public static function getBus()
    {
        $arrCommandClassToHandlerMap = array(
            "Voetbal\\Command\\SeasonAdd" => new Command\Handler\SeasonAdd(),
        );

        $handlerMiddleware = new CommandHandlerMiddleware(
            new ClassNameExtractor(),
            new InMemoryLocator( $arrCommandClassToHandlerMap ),
            new Inflector()
        );
        $lockingMiddleware = new LockingMiddleware();

        return new \League\Tactician\CommandBus([$lockingMiddleware,$handlerMiddleware]);
    }
}

$oYesterday = Carbon::yesterday();
$oTomorrow = Carbon::tomorrow();
$seasonname = new SeasonName("gister tot vandaag");
$command = new Command\SeasonAdd( $seasonname, new Period( $oYesterday, $oTomorrow ) );
Service::getBus()->handle( $command );

