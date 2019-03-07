<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:53
 */

namespace Voetbal\External\System\FootballData;

use Voetbal\External\System as ExternalSystem;
use Voetbal\External\Season as ExternalSeason;
use Voetbal\External\League as ExternalLeague;
use Monolog\Logger;
use GuzzleHttp\Client;

class ApiHelper
{
    /**
    * @var ExternalSystem
    */
    private $externalSystem;
    /**
     * @var array
     */
    private $requests;
    /**
     * @var Client
     */
    private $client;
    /**
     * @var int
     */
    private $nrOfRequestLeft;
    /**
     * @var int
     */
    private $waitTime = 60;

    public function __construct(
        ExternalSystem $externalSystem
    )
    {
        $this->requests = [];
        $this->externalSystem = $externalSystem;
    }

    protected function getClient() {
        if( $this->client === null ) {
            $this->client = new Client();
        }
        return $this->client;
    }

    protected function getHeaders() {
        return array('headers' => array('X-Auth-Token' => $this->externalSystem->getApikey()));
    }

    public function getData( $postUrl ) {
        if( array_key_exists( $postUrl, $this->requests ) ) {
            return $this->requests[$postUrl];
        }
        if( $this->nrOfRequestLeft === 0 ) {
            sleep($this->waitTime);
        }
        $response = $this->getClient()->get(
            $this->externalSystem->getApiurl() . $postUrl,
            $this->getHeaders()
        );
        if( count($response->getHeader('X-Requests-Available-Minute')) === 1 ) {
            $nrOfRequestLeftAsArray = $response->getHeader('X-Requests-Available-Minute');
            $this->nrOfRequestLeft = (int) reset($nrOfRequestLeftAsArray);
        }
        $this->requests[$postUrl] = json_decode($response->getBody());
        return $this->requests[$postUrl];
    }

    public function getLeague( ExternalLeague $externalLeague ): ?\StdClass
    {
        $leagues = $this->getData("competitions/?plan=TIER_ONE")->competitions;
        $foundLeagues = array_filter( $leagues, function ( $league ) use ( $externalLeague ) {
            return $league->id === (int)$externalLeague->getExternalId();
        });
        if( count( $foundLeagues ) !== 1 ) {
            return null;
        }
        return reset($foundLeagues);
    }

    public function getCompetition( ExternalLeague $externalLeague, ExternalSeason $externalSeason ): ?\StdClass
    {
        $externalSystemLeague = $this->getLeague($externalLeague);
        if( $externalSystemLeague === null ) {
            return null;
        }

        $externalSystemLeagueDetails = $this->getData("competitions/". $externalSystemLeague->id);

        $leagueSeaons = $externalSystemLeagueDetails->seasons;
        $foundLeagueSeaons = array_filter( $leagueSeaons, function ( $leagueSeaon ) use ( $externalSeason ) {
            return substr($leagueSeaon->startDate, 0, 4 ) === $externalSeason->getExternalId();
        });
        if( count( $foundLeagueSeaons ) !== 1 ) {
            return null;
        }
        return reset($foundLeagueSeaons);
    }

    public function getRounds( ExternalLeague $externalLeague, ExternalSeason $externalSeason ): ?array
    {
        $matches = $this->getGames($externalLeague,$externalSeason);
        if( $this->getRoundsHelperAllMatchesHaveDate( $matches ) !== true ) {
            return [];
        }
        uasort( $matches, function( $matchA, $matchB ) {
            return $matchA->utcDate < $matchB->utcDate;
        } );

        $rounds = [];
        foreach( $matches as $match) {
            if( array_search( $match->stage, $rounds ) !== false ) {
                $round = new \stdClass();
                $round->name = $match->stage;
                $round->nrPlacesPerPoule = $this->getRoundsHelperGetNrOfPlacesPerPoule( $matches, $match->stage );
                $rounds[] = $match->stage;
            }
        }
        return $rounds;
    }

    protected function getRoundsHelperAllMatchesHaveDate( array $matches ): bool
    {
        foreach( $matches as $match) {
            if( strlen( $match->utcDate ) === 0  ) {
                return false;
            }
        }
        return true;
    }


    protected function getRoundsHelperGetNrOfPlacesPerPoule( array $matches, string $stage ): array
    {
        $stageMatches = array_filter( $matches, function( $match ) use ($stage) {
            return $match->stage === $stage;
        });
        $placesPerPoule = $this->getRoundsHelperGetPlacesPerPoule( $stageMatches );
        if( count($placesPerPoule) === 0 ) {
            throw new \Exception("no places to be found for stage " . $stage, E_ERROR );
        }
        return array_map( function( $placesPerPoule ) {
            return count($placesPerPoule);
        }, $placesPerPoule );
    }

    protected function getRoundsHelperGetPlacesPerPoule( array $stageMatches ): array
    {
        $poules = [];
        foreach( $stageMatches as $stageMatch ) {
            if( $stageMatch->homeTeam === null || $stageMatch->awayTeam === null ) {
                continue;
            }
            $homeTeamId = $stageMatch->homeTeam->id;
            $awayTeamId = $stageMatch->awayTeam->id;
            $found = false;
            foreach( $poules as $poule ) {
                if( array_search( $homeTeamId, $poule ) || array_search( $awayTeamId, $poule ) ) {
                    $found = true;
                    break;
                }
            }
            if( !$found ) {
                $poules[] = [$homeTeamId, $awayTeamId];
            }
        }
        return $poules;
    }

    public function getCompetitors( ExternalLeague $externalLeague, ExternalSeason $externalSeason ): ?array
    {
        $retVal = $this->getData("competitions/".$externalLeague->getExternalId()."/teams?season=".$externalSeason->getExternalId() );
        return $retVal->teams;
    }

    public function getGames( ExternalLeague $externalLeague, ExternalSeason $externalSeason, string $stage = null /* round */ ): ?array
    {
        $retVal = $this->getData("competitions/".$externalLeague->getExternalId()."/matches?season=".$externalSeason->getExternalId() );
        if( $stage === null ) {
            return $retVal->matches;
        }
        return array_filter( $retVal->matches, function( $match ) use ($stage) {
            return $match->stage === $stage;
        });
    }

    public function getDate( string $date ) {
        if( strlen( $date ) === 0 ) {
            return null;
        }
        return \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $date);
    }
}