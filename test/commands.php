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
            "Voetbal\\Command\\LeagueAdd" => new Command\Handler\LeagueAdd()
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

$command = new Command\SeasonAdd(
    new Season\Name("2016/2017"),
    new Period( Carbon::create( 2016, 9, 1, 0 ), Carbon::create( 2017, 7, 1, 0 ) )
);
$oSeason = Service::getBus()->handle( $command );

$command = new Command\LeagueAdd(
    new League\Name("eredivisie"),
    new League\Abbreviation("ere")
);
$oLeague = Service::getBus()->handle( $command );

return;

// @TODO competitionadd-command should check if the combination does not exists, entitymanager should be injected in the command
$command = new Command\CompetitionAdd(
    $oLeague,
    $oSeason
);
$command->putAssociation();
$command->putPromotionRule();
$command->putNrOfMinutesGame();
$command->putNrOfMinutesExtraTime();
$command->putWinPointsAfterGame();
$command->putWinPointsAfterExtraTime();
$command->putWinPointsAfterExtraTime();

// @TODO $command->putExternId();, should maybe go through an import object to check uniqueness

$oCompetition = Service::getBus()->handle( $command );

/*Public                    deze eigenschapp zou een eigen klasse moeten hebben CompetitionPublish


ExternId*/