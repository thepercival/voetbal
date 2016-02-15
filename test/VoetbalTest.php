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

$arrCommandClassToHandlerMap = array(
    "Voetbal\\Command\\AddSeason" => new Command\Handler\AddSeason(),
);
class Inflector implements MethodNameInflector
{
    public function inflect($command, $commandHandler)
    {
        return 'handle';
    }
}
$handlerMiddleware = new CommandHandlerMiddleware(
    new ClassNameExtractor(),
    new InMemoryLocator( $arrCommandClassToHandlerMap ),
    new Inflector()
);
$lockingMiddleware = new LockingMiddleware();

$commandBus = new \League\Tactician\CommandBus([$lockingMiddleware,$handlerMiddleware]);
$oYesterday = Carbon::yesterday();
$oTomorrow = Carbon::tomorrow();
$seasonname = new SeasonName("gister tot vandaag");
$command = new Command\AddSeason( $seasonname, new Period( $oYesterday, $oTomorrow ) );
$commandBus->handle( $command );

