<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 15-2-2016
 * Time: 10:21
 */

namespace Voetbal;

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$isDevMode = true;
// $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../src"), $isDevMode);
//$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
$config = Setup::createYAMLMetadataConfiguration(array( realpath( __DIR__."/../config/")."db.dcm.yml"), $isDevMode);

$dbParams = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'cdk4',
    'dbname'   => 'voetbal',
);
$entityManager = EntityManager::create($dbParams, $config);

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);

