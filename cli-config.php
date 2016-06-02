<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 15-2-2016
 * Time: 11:04
 */

namespace Voetbal;

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once __DIR__ . '/vendor/autoload.php';

// replace with mechanism to retrieve EntityManager in your app
$isDevMode = true;
$config = Setup::createYAMLMetadataConfiguration(array(realpath( __DIR__ ."/db" )), $isDevMode);
$arrConfig = parse_ini_file( __DIR__ . "/config/voetbal.ini", true );
$entityManager = EntityManager::create( $arrConfig["database"], $config);
// $entityManager = GetEntityManager();

return ConsoleRunner::createHelperSet($entityManager);
