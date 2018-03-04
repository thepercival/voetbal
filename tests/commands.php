<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 9-2-2016
 * Time: 09:12
 */

/*
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
}

try {
    $command = new Command\AssociationAdd( new Association\Name( "F.I.F.A." ) );
    $command->setDescription( new Association\Description( "wereld voetbalbond" ) );
    $oAssociationWorld = Service::getBus()->handle( $command );

    $command = new Command\AssociationAdd( new Association\Name( "U.E.F.A." ) );
    $command->setDescription( new Association\Description( "europese voetbalbond" ) );
    $command->setParent( $oAssociationWorld );
    $oAssociationEurope = Service::getBus()->handle( $command );

    $command = new Command\SeasonAdd(
        new Season\Name( "2016/2017" ),
        new Period( Carbon::create( 2016, 9, 1, 0 ), Carbon::create( 2017, 7, 1, 0 ) )
    );
    $oSeason = Service::getBus()->handle( $command );

    $command = new Command\LeagueAdd(
        new League\Name( "eredivisie" ),
        new League\Abbreviation( "ere" )
    );
    $oLeague = Service::getBus()->handle( $command );
}
catch( \Exception $e )
{
    echo $e->getMessage() . PHP_EOL;
}
return;

// @TODO leagueadd-command should check if the combination does not exists, entitymanager should be injected in the command
$command = new Command\LeagueAdd(
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

$oLeague = Service::getBus()->handle( $command );
*/
/*Public                    deze eigenschapp zou een eigen klasse moeten hebben LeaguePublish


ExternId*/