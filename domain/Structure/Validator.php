<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-1-19
 * Time: 14:35
 */

namespace Voetbal\Structure;

use Voetbal\NameService;
use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Poule;
use Voetbal\Round;
use Voetbal\Association;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Competition;
use Voetbal\Structure;

/**
 * Repository
 *
 */
class Validator
{
    /**
     * @var Repository
     */
    protected $structureRepos;
    /**
     * @var NameService
     */
    protected $nameService;

    public function __construct(
        Repository $structureRepos
    ) {
        $this->structureRepos = $structureRepos;
        $this->nameService = new NameService();
    }

    public function checkValidity(Competition $competition)
    {
        $prefix = "de structuur(competition-id:".$competition->getId().")";

        $structure = $this->structureRepos->getStructure($competition);
        if (!($structure instanceof Structure)) {
            throw new \Exception($prefix . " heeft geen rondenummers", E_ERROR);
        }

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = null;
        if ($firstRoundNumber->getRounds()->count() === 1) {
            $rootRound = $firstRoundNumber->getRounds()->first();
        }
        if (!($rootRound instanceof Round)) {
            throw new \Exception($prefix . " heeft geen ronden", E_ERROR);
        }

        $association = $competition->getLeague()->getAssociation();
        $this->checkRoundNumberValidity($firstRoundNumber, $association);
        foreach ($firstRoundNumber->getRounds() as $round) {
            $this->checkRoundValidity($round);
        }
    }

    public function checkRoundNumberValidity(RoundNumber $roundNumber, Association $association)
    {
        $prefix = "rondenummer " . $roundNumber->getNumber() . " (".$roundNumber->getId().")";
        if ($roundNumber->getRounds()->count() === 0) {
            throw new \Exception($prefix . " bevat geen ronden", E_ERROR);
        }
        if ($roundNumber->getValidSportScoreConfigs()->count() === 0) {
            throw new \Exception($prefix . " bevat geen geldige sportscoreconfig", E_ERROR);
        }
        foreach ($roundNumber->getCompetitors() as $competitor) {
            if ($competitor->getAssociation() !== $association) {
                throw new \Exception("deelnemerid " . $competitor->getId() . " heeft een andere bond", E_ERROR);
            }
        }
        if ($roundNumber->hasNext()) {
            $this->checkRoundNumberValidity($roundNumber->getNext(), $association);
        }
    }

    /**
     * @param Round $round
     */
    public function checkRoundValidity(Round $round)
    {
        if ($round->getPoules()->count() === 0) {
            throw new \Exception("ronde-id " . $round->getId() . " bevat geen poules", E_ERROR);
        }

        foreach ($round->getPoules() as $poule) {
            $this->checkPouleValidity($poule);
        }

        if (!$round->getNumber()->hasNext() && $round->getQualifyGroups()->count() > 0) {
            throw new \Exception("ronde-id " . $round->getId() . " heeft geen volgende ronde, maar wel kwalificatiegroepen", E_ERROR);
        }

        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $this->checkRoundValidity($qualifyGroup->getChildRound());
        }
    }

    /**
     * @param Poule $poule
     * @throws \Exception
     */
    public function checkPouleValidity(Poule $poule)
    {
        $prefix = "poule-id " . $poule->getId() . "(".$this->nameService->getPouleName($poule, false).", rondenummer: ".$poule->getRound()->getNumberAsValue()." )";
        if ($poule->getPlaces()->count() === 0) {
            throw new \Exception($prefix . " bevat geen plekken", E_ERROR);
        }

        if ($poule->getGames()->count() === 0) {
            throw new \Exception($prefix . " bevat geen wedstrijden", E_ERROR);
        }
    }


    /*public function remove(Structure $structure, int $roundNumberValue = null )
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $roundNumber = $structure->getRoundNumber( $roundNumberValue ? $roundNumberValue : 1);
            if( $roundNumber === null ) {
                throw new \Exception("rondenummer " . $roundNumberValue . " kon niet gevonden worden", E_ERROR);
            }
            $this->em->remove($roundNumber);
            $this->em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }*/
}
