<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\ExternalSource\SofaScore\Helper;

use stdClass;
use Voetbal\ExternalSource\SofaScore\Helper as SofaScoreHelper;
use Voetbal\ExternalSource\SofaScore\ApiHelper as SofaScoreApiHelper;
use Voetbal\Competition;
use Psr\Log\LoggerInterface;
use Voetbal\Import\Service as ImportService;
use Voetbal\ExternalSource\SofaScore;
use Voetbal\Range as VoetbalRange;
use Voetbal\Structure as StructureBase;
use Voetbal\ExternalSource\Structure as ExternalSourceStructure;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Structure\Options as StructureOptions;

class Structure extends SofaScoreHelper implements ExternalSourceStructure
{
    /**
     * @var StructureService
     */
    protected $structureService;

    public function __construct(
        SofaScore $parent,
        SofaScoreApiHelper $apiHelper,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $parent,
            $apiHelper,
            $logger
        );
        $options = new StructureOptions(
            new VoetbalRange(1, 64), // pouleRange
            new VoetbalRange(1, 128), // placeRange
            new VoetbalRange(1, 40) // placesPerPouleRange
        );
        $this->structureService = new StructureService($options);
    }

    public function getStructure(Competition $competition): ?StructureBase
    {
        list($nrOfPlaces, $nrOfPoules) = $this->getPlacesAndPoules($competition);
        if ($nrOfPlaces === 0 || $nrOfPoules === 0) {
            return null;
        }
        $competitors = $this->parent->getCompetitors($competition);
        $structure = $this->structureService->create($competition, $nrOfPlaces, $nrOfPoules);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        foreach ($firstRoundNumber->getPoules() as $poule) {
            foreach ($poule->getPlaces() as $place) {
                $competitor = array_shift($competitors);
                if ($competitor === null) {
                    return null;
                }
                $place->setCompetitor($competitor);
            }
        }

        return $structure;
    }

    protected function getPlacesAndPoules(Competition $competition): array
    {
        $apiData = $this->apiHelper->getCompetitionData($competition);
        return $this->getPlacesAndPoulesHelper($apiData);
    }

    protected function getPlacesAndPoulesHelper($apiData)
    {
        $nrOfPlaces = 0;
        $nrOfPoules = 0;
        if (property_exists($apiData, 'standingsTables')) {
            foreach ($apiData->standingsTables as $standingsTable) {
                $nrOfPoules++;
                $nrOfPlaces += count($standingsTable->tableRows);
            }
        }
        return [$nrOfPlaces,$nrOfPoules];
    }
}
