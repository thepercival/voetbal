<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\ExternalSource\SofaScore\Helper;

use Voetbal\ExternalSource\SofaScore\Helper as SofaScoreHelper;
use Voetbal\ExternalSource\SofaScore\ApiHelper as SofaScoreApiHelper;
use Voetbal\Competition as CompetitionBase;
use Voetbal\ExternalSource;
use Psr\Log\LoggerInterface;
use Voetbal\Import\Service as ImportService;
use Voetbal\ExternalSource\SofaScore;

use Voetbal\ExternalSource\Competition as ExternalSourceCompetition;

class Competition extends SofaScoreHelper implements ExternalSourceCompetition
{
    /**
     * @var array|CompetitionBase[]|null
     */
    protected $competitions;

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
    }

    /**
     * @return array|CompetitionBase[]
     */
    public function getCompetitions(): array
    {
        $apiData = $this->apiHelper->getData(
            "football//" . $this->apiHelper->getCurrentDateAsString() . "/json",
            ImportService::COMPETITION_CACHE_MINUTES );
        return $this->getCompetitionsHelper($apiData->sportItem->tournaments);
    }

    public function getCompetition( $id = null ): ?CompetitionBase
    {
        $competitions = $this->getCompetitions();
        if( array_key_exists( $id, $competitions ) ) {
            return $competitions[$id];
        }
        return null;
    }


    /**
     * {"name":"Premier Competition 19\/20","slug":"premier-competition-1920","year":"19\/20","id":23776}
     *
     * @param array $competitions |stdClass[]
     * @return array|CompetitionBase[]
     */
    protected function getCompetitionsHelper(array $competitions): array
    {
        if( $this->competitions !== null ) {
            return $this->competitions;
        }
        $this->competitions = [];
        foreach ($competitions as $competition) {

            if( $competition->tournament === null ) {
                continue;
            }
            $league = $this->parent->getLeague( $competition->tournament->id );
            if( $league === null ) {
                continue;
            }

            if( $competition->season === null ) {
                continue;
            }
            if( strlen( $competition->season->year ) === 0 ) {
                continue;
            }
            $season = $this->parent->getSeason( $competition->season->year );
            if( $season === null ) {
                continue;
            }

            $newCompetition = new CompetitionBase( $league, $season );
            $newCompetition->setStartDateTime( $season->getStartDateTime() );
            $newCompetition->setId( $competition->season->id );
            $this->competitions[$newCompetition->getId()] = $newCompetition;
        }
        return $this->competitions;
    }

}