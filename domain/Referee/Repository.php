<?php

namespace Voetbal\Referee;

/**
 * Class Repository
 * @package Voetbal
 */
class Repository extends \Voetbal\Repository
{
    public function saveFromJSON( Referee $referee, Competitionseason $competitionseason )
    {
        $referee->setCompetitionseason( $competitionseason );
        $this->_em->persist( $referee );
    }

    public function editFromJSON( Referee $p_referee, Competitionseason $competitionseason )
    {
        $referee = $competitionseason->getReferee( $p_referee->getNumber() );
        $referee->setName( $p_referee->getName() );
        $this->_em->persist( $referee );
        $this->_em->flush();
        return $referee;
    }
}
