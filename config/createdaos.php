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
use Doctrine\ORM\Configuration;

require_once __DIR__ . '/../vendor/autoload.php';

//if ($applicationMode == "development") {
    $cache = new \Doctrine\Common\Cache\ArrayCache;
//} else {
  //  $cache = new \Doctrine\Common\Cache\ApcCache;
//}

// $config = new Configuration;
// $config->setMetadataCacheImpl($cache);
// $driverImpl = $config->newDefaultYamlDriver( __DIR__ . '/db');

$isDevMode = true;
$config = Setup::createYAMLMetadataConfiguration(array(realpath( __DIR__ ."/db" )), $isDevMode, __DIR__ . '/db/proxies', $cache);
// $config->setMetadataDriverImpl($driverImpl);
$config->setQueryCacheImpl($cache);
$config->setProxyNamespace('Voetbal\Proxies');

// if ($applicationMode == "development") {
    $config->setAutoGenerateProxyClasses(true);
// } else {
   //  $config->setAutoGenerateProxyClasses(false);
// }

// replace with mechanism to retrieve EntityManager in your app
// $isDevMode = true;
// $config = Setup::createYAMLMetadataConfiguration(array(realpath( __DIR__ ."/db" )), $isDevMode);
$dbParams = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'cdk4',
    'dbname'   => 'voetbal',
);
$entityManager = EntityManager::create($dbParams, $config);



