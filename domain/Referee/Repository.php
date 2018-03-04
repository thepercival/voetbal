<?php

namespace Voetbal\Referee;

use Voetbal\Competition;
use Voetbal\Referee;

/**
 * Class Repository
 * @package Voetbal
 */
class Repository extends \Voetbal\Repository
{
    public function saveFromJSON( Referee $referee, Competition $competition )
    {
        $referee->setCompetition( $competition );
        $this->_em->persist( $referee );
    }

    public function editFromJSON( Referee $p_referee, Competition $competition )
    {
        $referee = $competition->getRefereeById( $p_referee->getId() );
        $referee->setName( $p_referee->getName() );
        $referee->setInfo( $p_referee->getInfo() );
        $this->_em->persist( $referee );
        $this->_em->flush();
        return $referee;
    }
}
