<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-1-19
 * Time: 7:52
 */

namespace Voetbal\Competitor;

use Voetbal\Round;
use Voetbal\Association;
use Voetbal\Structure;
use Voetbal\Competitor;

class Service
{
    public function createTeamsFromRound(Round $rootRound, Association $association)
    {
        $teams = [];
        $poulePlaces = $rootRound->getPoulePlaces();
        foreach ($poulePlaces as $poulePlace) {
            $team = $poulePlace->getTeam();
            if ($team !== null) {
                $newTeam = new Competitor($team->getName(), $association);
                $newTeam->setAbbreviation($team->getAbbreviation());
                $newTeam->setImageUrl($team->getImageUrl());
                $newTeam->setInfo($team->getInfo());
                $teams[] = $newTeam;
            }
        }
        return $teams;
    }

    public function assignCompetitors( Structure $newStructure, array $newTeams )
    {
        foreach( $newStructure->getRootRound()->getPoulePlaces() as $poulePlace ) {
            $poulePlace->setCompetitor(null);
            $poulePlace->setCompetitor(array_shift($newTeams));
        }
        foreach( $newStructure->getRootRound()->getChildRounds() as $childRound ) {
            $this->removeQualifiedCompetitors( $childRound );
        }
    }

    protected function removeQualifiedCompetitors( Round $round)
    {
        foreach( $round->getPoules() as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                $place->setCompetitor(null);
            }
        }
        foreach( $round->getChildRounds() as $childRound ) {
            $this->removeQualifiedCompetitors( $childRound );
        }
    }
}