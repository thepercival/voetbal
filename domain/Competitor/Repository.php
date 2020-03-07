<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 13:17
 */

namespace Voetbal\Competitor;

use Voetbal\Competitor as CompetitorBase;

/**
 * Class Repository
 * @package Voetbal\Competitor
 */
class Repository extends \Voetbal\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?CompetitorBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}