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
use Voetbal\Sport\Config\Service as SportConfigService;
use Voetbal\Sport;
use Voetbal\ExternalSource\Competition as ExternalSourceCompetition;

class Competition extends SofaScoreHelper implements ExternalSourceCompetition
{
    /**
     * @var array|CompetitionBase[]|null
     */
    protected $competitions;
    protected $sportConfigService;

    public function __construct(
        SofaScore $parent,
        SofaScoreApiHelper $apiHelper,
        LoggerInterface $logger
    ) {
        $this->sportConfigService = new SportConfigService();
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
        if( $this->competitions !== null ) {
            return $this->competitions;
        }
        $this->competitions = [];

        $sports = $this->parent->getSports();
        foreach( $sports as $sport ) {
            if( $sport->getName() !== SofaScore::SPORTFILTER ) {
                continue;
            }
            $apiData = $this->apiHelper->getData(
                $sport->getName() . "//" . $this->apiHelper->getCurrentDateAsString() . "/json",
                ImportService::LEAGUE_CACHE_MINUTES );

            $this->setCompetitions( $sport, $apiData->sportItem->tournaments );
        }
        return $this->competitions;
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
     * @param Sport $sport
     * @param array|\stdClass[] $externalSourceCompetitions
     */
    protected function setCompetitions( Sport $sport, array $externalSourceCompetitions)
    {
        /** @var \stdClass $externalSourceCompetition */
        foreach ($externalSourceCompetitions as $externalSourceCompetition) {

            if( $externalSourceCompetition->tournament === null ) {
                continue;
            }
            $league = $this->parent->getLeague( $externalSourceCompetition->tournament->id );
            if( $league === null ) {
                continue;
            }

            if( $externalSourceCompetition->season === null ) {
                continue;
            }
            if( strlen( $externalSourceCompetition->season->year ) === 0 ) {
                continue;
            }
            $season = $this->parent->getSeason( $externalSourceCompetition->season->year );
            if( $season === null ) {
                continue;
            }

            $newCompetition = new CompetitionBase( $league, $season );
            $newCompetition->setStartDateTime( $season->getStartDateTime() );
            $newCompetition->setId( $externalSourceCompetition->season->id );
            $sportConfig = $this->sportConfigService->createDefault( $sport, $newCompetition );
            $this->competitions[$newCompetition->getId()] = $newCompetition;
        }
    }

}