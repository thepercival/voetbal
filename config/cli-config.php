<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 15-2-2016
 * Time: 11:04
 */

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once __DIR__ . '/../vendor/autoload.php';

// replace with mechanism to retrieve EntityManager in your app
$isDevMode = true;
$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."\\db.dcm.yml"), $isDevMode);
$dbParams = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'cdk4',
    'dbname'   => 'voetbal',
);
$entityManager = EntityManager::create($dbParams, $config);
// $entityManager = GetEntityManager();

return ConsoleRunner::createHelperSet($entityManager);
