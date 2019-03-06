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
//        1 item moet hebben:
//        1 name
//        2 nrOfPlaces,
//        3 nrOfPoules

        $retVal = $this->getData("competitions/".$externalLeague->getExternalId()."/matches?season=".$externalSeason->getExternalId() );
        if( $this->getRoundsHelperAllMatchesHaveDate( $retVal->matches ) !== true ) {
            return [];
        }

        uasort( $retVal->matches, function( $matchA, $matchB ) {
            return $matchA->utcDate < $matchB->utcDate;
        } );
        $rounds = [];
        foreach( $retVal->matches as $match) {
            if( array_search( $match->stage, $rounds ) !== false ) {
                $round = new \stdClass();
                $round->name = $match->stage;
                $round->nrOfPlaces = ?;
                $round->nrOfPoules = ?;

                $rounds[] = $match->stage;
            }
        }
        return $rounds;
    }

    protected function getRoundsHelperAllMatchesHaveDate( array $matches ): boolean
    {
        foreach( $matches as $match) {
            if( strlen( $match->utcDare ) === 0  ) {
                return false;
            }
        }
        return true;
    }

    public function getCompetitors( ExternalLeague $externalLeague, ExternalSeason $externalSeason ): ?array
    {
        $retVal = $this->getData("competitions/".$externalLeague->getExternalId()."/teams?season=".$externalSeason->getExternalId() );
        return $retVal->teams;
    }

    public function getGames( ExternalLeague $externalLeague, ExternalSeason $externalSeason ): ?array
    {
        $retVal = $this->getData("competitions/".$externalLeague->getExternalId()."/matches?season=".$externalSeason->getExternalId() );
        return $retVal->matches;
    }

    public function getDate( string $date ) {
        if( strlen( $date ) === 0 ) {
            return null;
        }
        return \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $date);
    }
}