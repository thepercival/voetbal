<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Voetbal\Round;

use Voetbal\Round;
use Voetbal\Round\Repository as RoundRepository;
use Voetbal\Poule\Repository as PouleRepository;
use Voetbal\Competition;
use Doctrine\DBAL\Connection;
use Voetbal\Poule;
use Voetbal\PoulePlace;
use Voetbal\Round\Structure as RoundStructure;
use Voetbal\Structure\Options as StructureOptions;
use Voetbal\Round\Config\Options as ConfigOptions;

class Service
{
    /**
     * @var RoundRepository
     */
    protected $repos;
    /**
     * @var Config\Service
     */
    protected $roundConfigService;
    /**
     * @var Competition\Repository
     */
    protected $competitionRepos;
    /**
     * @var Poule\Service
     */
    protected $pouleService;
    /**
     * @var PouleRepository
     */
    protected $pouleRepos;
    /**
     * @var PoulePlace\Service
     */
    protected $poulePlaceService;
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * Service constructor.
     * @param Repository $repos
     * @param Config\Service $configService
     * @param Competition\Repository $competitionRepos
     * @param Poule\Service $pouleService
     * @param PouleRepository $pouleRepos
     * @param Connection $conn
     */
    public function __construct(
        RoundRepository $repos,
        Config\Service $configService,
        Competition\Repository $competitionRepos,
        Poule\Service $pouleService,
        PouleRepository $pouleRepos,
        PoulePlace\Service $poulePlaceService,
        Connection $conn
    )
    {
        $this->repos = $repos;
        $this->configService = $configService;
        $this->competitionRepos = $competitionRepos;
        $this->pouleService = $pouleService;
        $this->pouleRepos = $pouleRepos;
        $this->poulePlaceService = $poulePlaceService;
        $this->conn = $conn;
    }

    public function generate( Competition $competition, int $winnersOrLosers, StructureOptions $structureOptions, Round $parent = null ): Round
    {
        $opposingChildRound = $parent ? $parent->getChildRound( Round::getOpposing($winnersOrLosers)) : null;
        $opposing = $opposingChildRound !== null ? $opposingChildRound->getWinnersOrLosers() : 0;

        $round = null;
        $this->conn->beginTransaction();
        try {
            $round = $this->generateHelper( $competition, $winnersOrLosers, $structureOptions, $opposing, $parent);
            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
        return $round;
    }

    private function generateHelper(
        Competition $competition,
        int $winnersOrLosers,
        StructureOptions $structureOptions,
        int $opposing,
        Round $parent = null
    ): Round
    {
        if ($structureOptions->round->nrofplaces <= 0) {
            throw new \Exception("het aantal plekken voor een nieuwe ronde moet minimaal 1 zijn", E_ERROR );
        }
        if ($structureOptions->round->nrofpoules <= 0) {
            throw new \Exception("het aantal poules voor een nieuwe ronde moet minimaal 1 zijn", E_ERROR );
        }

        $round = new Round($competition, $parent);
        $round->setWinnersOrLosers( $winnersOrLosers );
        $round = $this->repos->save($round);

        $nrOfPlaces = $structureOptions->round->nrofplaces;

        $nrOfPlacesPerPoule = $structureOptions->round->getNrOfPlacesPerPoule();
        $nrOfPlacesNextRound = ($winnersOrLosers === Round::LOSERS) ? ($nrOfPlaces - $structureOptions->round->nrofwinners) : $structureOptions->round->nrofwinners;
        $nrOfOpposingPlacesNextRound = (Round::getOpposing($winnersOrLosers) === Round::WINNERS) ? $structureOptions->round->nrofwinners : $nrOfPlaces - $structureOptions->round->nrofwinners;

        $pouleNumber = 1;

        while ($nrOfPlaces > 0) {
            $nrOfPlacesToAdd = $nrOfPlaces < $nrOfPlacesPerPoule ? $nrOfPlaces : $nrOfPlacesPerPoule;
            $poule = $this->pouleService->create( $round, $pouleNumber++, $nrOfPlacesToAdd );
            $nrOfPlaces -= $nrOfPlacesPerPoule;
        }

        $roundConfigOptions = $structureOptions->roundConfig;
//            if ($round->getParent() !== null) {
//                $roundConfigOptionsTmp = $round->getParent()->getConfig()->getOptions();
//            }
        $roundConfigOptions->setHasExtension(!$round->needsRanking());

        $this->configService->create($round, $roundConfigOptions);
        // this.configRepos.createObjectFromParent(round);

//        if ($parent !== null) {
//            $qualifyService = new QualifyService($round);
//            $qualifyService->createObjectsForParent();
//        }
//
        if ($structureOptions->round->nrofwinners === 0) {
            return $round;
        }

        $structureOptions->round = new RoundStructure( $nrOfPlacesNextRound );
        $this->generateHelper(
            $competition,
            $winnersOrLosers ? $winnersOrLosers : Round::WINNERS,
            $structureOptions,
            $opposing,
            $round
        );

        // $hasParentOpposingChild = ( $parent->getChild( Round::getOpposing( $winnersOrLosers ) )!== null );
        if ($opposing > 0 || ($round->getPoulePlaces()->count() === 2)) {
            $structureOptions->round = new RoundStructure( $nrOfOpposingPlacesNextRound );
            $opposing = $opposing > 0 ? $opposing : Round::getOpposing($winnersOrLosers);
            $this->generateHelper(
                $competition,
                $winnersOrLosers,
                $structureOptions,
                $opposing,
                $round
            );
        }
        return $round;
    }

    public function create(
        int $number,
        int $winnersOrLosers,
        int $qualifyOrder,
        ConfigOptions $configOptions,
        array $poulesSer,
        Competition $competition,
        Round $p_parent = null ): Round
    {
        $round = null;
        $this->conn->beginTransaction(); // suspend auto-commit
        try {

            if ( $number < 1 ) {
                throw new \Exception("een rondenummer moet minimaal 1 zijn", E_ERROR);
            }
            if ( count($poulesSer) <= 0) {
                throw new \Exception("het aantal poules voor een nieuwe ronde moet minimaal 1 zijn", E_ERROR );
            }

            $round = new Round($competition, $p_parent);
            $round->setWinnersOrLosers( $winnersOrLosers );
            $round->setQualifyOrder( $qualifyOrder );
            $round = $this->repos->save($round);

            $this->updatePoules( $round, $poulesSer );

            $this->configService->create($round, $configOptions);

            $this->conn->commit();
        } catch ( \Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }

        return $round;
    }

    public function updateOptions( Round $round, int $qualifyOrder, ConfigOptions $configOptions )
    {
        $round->setQualifyOrder( $qualifyOrder );
        $round = $this->repos->save($round);

        $this->configService->update($round->getConfig(), $configOptions);
    }

    public function updatePoules( Round $round, array $poulesSer ) {

        $pouleIds = $this->getNewPouleIds( $poulesSer );
        $poulePlacesSer = $this->getPlacesFromPoules( $poulesSer );
        $placeIds = $this->getNewPlaceIds( $poulePlacesSer );

        $this->conn->beginTransaction(); // suspend auto-commit
        try {
            $this->removeNonexistingPoules( $round->getPoules()->toArray(), $pouleIds );
            $this->removeNonexistingPlaces( $round->getPoulePlaces()->toArray(), $placeIds );
            foreach( $poulesSer as $pouleSer ) {
                $this->updatePoulesHelper( $pouleSer, $round );
            }

            $this->pouleService->updateStructure( $poulesSer, $round);

            $this->conn->commit();
        } catch ( \Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    protected function getNewPouleIds( array $poulesSer )
    {
        $pouleIds = [];
        foreach( $poulesSer as $pouleSer ) {
            $pouleIds[$pouleSer->getId()] = true;
        }
        return $pouleIds;
    }

    protected function removeNonexistingPoules( array $poules, array $pouleIds )
    {
        foreach( $poules as $poule ) {
            if( array_key_exists( $poule->getId(), $pouleIds ) === false ) {
                // var_dump("poule with id ".$poule->getId()." removed " );
                $this->pouleService->remove($poule);
            }
        }
    }

    protected function getNewPlaceIds( array $placesSer )
    {
        $placeIds = [];
        foreach( $placesSer as $placeSer ) {
            $placeIds[$placeSer->getId()] = true;
        }
        return $placeIds;
    }

    protected function getPlacesFromPoules( array $poules )
    {
        $places = [];
        foreach( $poules as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                $places[] = $place;
            }
        }
        return $places;
    }


    protected function removeNonexistingPlaces( array $places, array $placeIds )
    {
        foreach( $places as $place ) {
            if( array_key_exists( $place->getId(), $placeIds ) === false ) {
                $this->poulePlaceService->remove($place);
                // var_dump("pouleplace with id ".$place->getId()." removed " );
            }
        }
    }

    protected function updatePoulesHelper( Poule $pouleSer, Round $round): Poule
    {
        $poule = null;
        if( $pouleSer->getId() === null ) {
            $poule = $this->pouleService->create( $round, $pouleSer->getNumber() );
        }
        else {
            $poule = $this->pouleRepos->find($pouleSer->getId());
        }
        return $poule;
    }

    /**
     * @param Round $round
     */
    public function remove( Round $round )
    {
        if( $round->getParent() !== null ) {
            $round->getParent()->getChildRounds()->removeElement($round);
        }
        return $this->repos->remove($round);
    }

    /**
     * @param $nrOfTeams
     * @return []
     * @throws \Exception
     */
    public function getDefault( int $roundNr, int $nrOfPlaces ): RoundStructure
    {
        $roundStructure = new RoundStructure( $nrOfPlaces );
        if( $roundNr > 1 ) {
            if ( $nrOfPlaces > 1 && ( $nrOfPlaces % 2 ) !== 0 ) {
                throw new \Exception("het aantal(".$nrOfPlaces.") moet een veelvoud van 2 zijn na de eerste ronde", E_ERROR);
            }
            $roundStructure->nrofpoules = $nrOfPlaces / 2;
            $roundStructure->nrofwinners = $nrOfPlaces / 2;
            return $roundStructure;
        }
        if( $nrOfPlaces ===  5 ) { $roundStructure->nrofpoules = 1; $roundStructure->nrofpoules = 2; }
        else if( $nrOfPlaces ===  6 ) { $roundStructure->nrofpoules = 2; $roundStructure->nrofpoules = 2; }
        else if( $nrOfPlaces ===  8 ) { $roundStructure->nrofpoules = 2; $roundStructure->nrofpoules = 2; }
        else if( $nrOfPlaces ===  9 ) { $roundStructure->nrofpoules = 3; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 10 ) { $roundStructure->nrofpoules = 2; $roundStructure->nrofpoules = 2; }
        else if( $nrOfPlaces === 11 ) { $roundStructure->nrofpoules = 2; $roundStructure->nrofpoules = 2; }
        else if( $nrOfPlaces === 12 ) { $roundStructure->nrofpoules = 3; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 13 ) { $roundStructure->nrofpoules = 3; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 14 ) { $roundStructure->nrofpoules = 3; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 15 ) { $roundStructure->nrofpoules = 3; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 16 ) { $roundStructure->nrofpoules = 4; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 17 ) { $roundStructure->nrofpoules = 4; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 18 ) { $roundStructure->nrofpoules = 4; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 19 ) { $roundStructure->nrofpoules = 4; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 20 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 21 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 22 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 23 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 24 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 25 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 26 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 27 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 28 ) { $roundStructure->nrofpoules = 7; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 29 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 30 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 31 ) { $roundStructure->nrofpoules = 7; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 32 ) { $roundStructure->nrofpoules = 8; $roundStructure->nrofpoules =16; }
        else {
            throw new \Exception("het aantal teams moet minimaal 1 zijn en mag maximaal 32 zijn", E_ERROR);
        }
        return $roundStructure;
    }
}