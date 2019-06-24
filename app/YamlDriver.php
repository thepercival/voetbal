<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 13-6-2019
 * Time: 12:06
 */

namespace VoetbalApp;

use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\Mapping\Driver\YamlDriver as DoctrineYamlDriver;

class YamlDriver extends DoctrineYamlDriver
{
    protected function loadMappingFile($file)
    {
        return Yaml::parse(file_get_contents($file), Yaml::PARSE_CONSTANT);
    }
}