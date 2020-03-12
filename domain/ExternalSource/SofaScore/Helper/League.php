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
use Voetbal\League as LeagueBase;
use Voetbal\ExternalSource;
use Psr\Log\LoggerInterface;
use Voetbal\Import\Service as ImportService;
use Voetbal\ExternalSource\SofaScore;

use Voetbal\ExternalSource\League as ExternalSourceLeague;

class League extends SofaScoreHelper implements ExternalSourceLeague
{
    /**
     * @var array|LeagueBase[]|null
     */
    protected $leagues;

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
     * @return array|LeagueBase[]
     */
    public function getLeagues(): array
    {
        if( $this->leagues !== null ) {
            return $this->leagues;
        }
        $this->leagues = [];

        $sports = $this->parent->getSports();

        $leagueData = [];
        foreach( $sports as $sport ) {
            if( $sport->getName() !== SofaScore::SPORTFILTER ) {
                continue;
            }
            $apiData = $this->apiHelper->getData(
                $sport->getName() . "//" . $this->apiHelper->getCurrentDateAsString() . "/json",
                ImportService::LEAGUE_CACHE_MINUTES );
            $leagueData = array_merge( $leagueData, $apiData->sportItem->tournaments );
        }
        $this->setLeagues( $leagueData );
        return $this->leagues;
    }

    public function getLeague( $id = null ): ?LeagueBase
    {
        $leagues = $this->getLeagues();
        if( array_key_exists( $id, $leagues ) ) {
            return $leagues[$id];
        }
        return null;
    }


    /**
     * {"name":"Premier League 19\/20","slug":"premier-league-1920","year":"19\/20","id":23776}
     *
     * @param array|\stdClass[] $competitions
     */
    protected function setLeagues(array $competitions)
    {
        foreach ($competitions as $competition) {

            if( $competition->category === null ) {
                continue;
            }
            $association = $this->parent->getAssociation( $competition->category->id );
            if( $association === null ) {
                continue;
            }
            if( $competition->tournament === null ) {
                continue;
            }
            $name = $competition->tournament->name;
            if( $this->hasName( $this->leagues, $name ) ) {
                continue;
            }
            $league = new LeagueBase( $association, $name );
            $league->setId( $competition->tournament->id );
            $this->leagues[$league->getId()] = $league;
        }
    }

}