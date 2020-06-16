<?php

namespace Voetbal\Referee;

use Voetbal\Referee;

/**
 * Class Repository
 * @package Voetbal
 */
class Repository extends \Voetbal\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Referee
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
