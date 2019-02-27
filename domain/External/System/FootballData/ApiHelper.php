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
            sleep(60);
        }
        $response = $this->getClient()->get(
            $this->externalSystem->getApiurl() . $postUrl,
            $this->getHeaders()
        );
        if( count($response->getHeader('X-Requests-Available-Minute')) > 0 ) {
            $x = $response->getHeader('X-Requests-Available-Minute');

            // $this->nrOfRequestLeft;
        }

        $json = $response->json();

        $this->requests[$postUrl] = $json;
        return $this->requests[$postUrl];
    }

    public function getLeague( ExternalLeague $externalLeague ): ?\StdClass
    {
        $leagues = $this->getData("competitions/?plan=TIER_ONE")->competitions;
        $foundLeagues = array_filter( $leagues, function ( $league ) use ( $externalLeague ) {
            return $league->id === $externalLeague->getExternalId();
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

    public function getCompetitors( ExternalLeague $externalLeague, ExternalSeason $externalSeason ): ?array
    {
        $retVal = $this->getData("competitions/".$externalLeague->getExternalId()."/teams?season=".$externalSeason->getExternalId() );
        return $retVal->teams;
    }

    public function getDate( string $date ) {
        if( strlen( $date ) === 0 ) {
            return null;
        }
        return \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $date);
    }
}