<?php

namespace Voetbal\Field;

use Voetbal\Field;

/**
 * Class Repository
 * @package Voetbal
 */
class Repository extends \Voetbal\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Field
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
