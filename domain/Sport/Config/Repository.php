<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Voetbal\Sport\Config;

use Voetbal\Sport\Repository as SportRepository;
use Voetbal\Sport\Config as SportConfig;

/**
 * Class Repository
 * @package Voetbal\Config\Score
 */
class Repository extends \Voetbal\Repository
{
    public function customRemove( SportConfig $sportConfig, SportRepository $sportRepos )
    {
        $sport = $sportConfig->getSport();
        $this->remove($sportConfig);

        if ( $this->findOneBy( ["sport" => $sport ] ) === null ) {
            $sportRepos->remove($sport);
        }
    }
}