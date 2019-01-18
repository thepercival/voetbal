<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-1-19
 * Time: 7:52
 */

namespace Voetbal\Team;

use Voetbal\Round;
use Voetbal\Association;
use Voetbal\Structure;
use Voetbal\Team;

class Service
{
    public function createTeamsFromRound(Round $rootRound, Association $association)
    {
        $teams = [];
        $poulePlaces = $rootRound->getPoulePlaces();
        foreach ($poulePlaces as $poulePlace) {
            $team = $poulePlace->getTeam();
            if ($team !== null) {
                $newTeam = new Team($team->getName(), $association);
                $newTeam->setAbbreviation($team->getAbbreviation());
                $newTeam->setImageUrl($team->getImageUrl());
                $newTeam->setInfo($team->getInfo());
                $teams[] = $newTeam;
            }
        }
        return $teams;
    }

    public function assignTeams( Structure $newStructure, array $newTeams )
    {
        foreach( $newStructure->getRootRound()->getPoulePlaces() as $poulePlace ) {
            $poulePlace->setTeam(null);
            $poulePlace->setTeam(array_shift($newTeams));
        }
        foreach( $newStructure->getRootRound()->getChildRounds() as $childRound ) {
            $this->removeQualifiedTeams( $childRound );
        }
    }

    protected function removeQualifiedTeams( Round $round)
    {
        foreach( $round->getPoules() as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                $place->setTeam(null);
            }
        }
        foreach( $round->getChildRounds() as $childRound ) {
            $this->removeQualifiedTeams( $childRound );
        }
    }
}