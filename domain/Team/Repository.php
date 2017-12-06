<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 13:17
 */

namespace Voetbal\Team;

use Voetbal\Team;
use Voetbal\Association;

/**
 * Team
 *
 */
class Repository extends \Voetbal\Repository
{
    public function editFromJSON( Team $team, Association $association )
    {
        $team->setAssociation( $association );
        $teamRet = $this->_em->merge( $team );
        $this->_em->flush();
        return $teamRet;
    }
}