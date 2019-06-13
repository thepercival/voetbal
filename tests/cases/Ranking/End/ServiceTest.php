<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 13-6-2019
 * Time: 09:25
 */

namespace Voetbal\Tests\Ranking\End;

include_once __DIR__ . '/../../../data/CompetitionCreator.php';
include_once __DIR__ . '/../../../Helpers/SetScores.php';

use Voetbal\Structure\Service as StructureService;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Qualify\Service as QualifyService;
use Voetbal\Ranking\Service as RankingService;
use Voetbal\Ranking\End\Service as EndRankingService;
use Voetbal\Planning\Service as PlanningService;
use Voetbal\Competitor;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testOnePouleOfThreePlaces()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        setScoreSingle($pouleOne, 1, 2, 2, 1);
        setScoreSingle($pouleOne, 1, 3, 3, 1);
        setScoreSingle($pouleOne, 2, 3, 3, 2);

        $endRankingService = new EndRankingService($structure, RankingService::RULESSET_WC);
        $items = $endRankingService->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            $this->assertSame($items[$rank - 1]->getName(), '0' . $rank);
            $this->assertSame($items[$rank - 1]->getUniqueRank(), $rank);
        }
    }

    public function testOnePouleOfThreePlacesWithNoCompetitor()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        $competitor1 = new Competitor($competition->getLeague()->getAssociation(), '01');
        $competitor2 = new Competitor($competition->getLeague()->getAssociation(), '02');
        $pouleOne->getPlace(1)->setCompetitor($competitor1);
        $pouleOne->getPlace(2)->setCompetitor($competitor2);

        setScoreSingle($pouleOne, 1, 2, 2, 1);
        setScoreSingle($pouleOne, 1, 3, 3, 1);
        setScoreSingle($pouleOne, 2, 3, 3, 2);

        $endRankingService = new EndRankingService($structure, RankingService::RULESSET_WC);
        $items = $endRankingService->getItems();

        $this->assertSame($items[2]->getName(), 'onbekend' );
    }

    public function testOnePouleOfThreePlacesNotPlayed()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }
        setScoreSingle($pouleOne, 1, 2, 2, 1);
        setScoreSingle($pouleOne, 1, 3, 3, 1);
        // setScoreSingle($pouleOne, 2, 3, 3, 2);

        $endRankingService = new EndRankingService($structure, RankingService::RULESSET_WC);
        $items = $endRankingService->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            $this->assertSame($items[$rank - 1]->getName(), 'nog onbekend');
        }
    }

    public function testTwoRoundNumbers5()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 5);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
        $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        setScoreSingle($pouleOne, 1, 2, 2, 1);
        setScoreSingle($pouleOne, 1, 3, 3, 1);
        setScoreSingle($pouleOne, 1, 4, 4, 1);
        setScoreSingle($pouleOne, 1, 5, 5, 1);
        setScoreSingle($pouleOne, 2, 3, 3, 2);
        setScoreSingle($pouleOne, 2, 4, 4, 2);
        setScoreSingle($pouleOne, 2, 5, 5, 2);
        setScoreSingle($pouleOne, 3, 4, 4, 3);
        setScoreSingle($pouleOne, 3, 5, 5, 3);
        setScoreSingle($pouleOne, 4, 5, 5, 4);

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);
        setScoreSingle($winnersPoule, 1, 2, 2, 1);
        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);
        setScoreSingle($loserssPoule, 1, 2, 2, 1);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $endRankingService = new EndRankingService($structure, RankingService::RULESSET_WC);
        $items = $endRankingService->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            $this->assertSame($items[$rank - 1]->getName(), '0' . $rank);
        }
    }
}