<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\ExternalSource\SofaScore\Helper;

use Cake\Chronos\Date;
use League\Period\Period;
use Voetbal\ExternalSource\SofaScore\Helper as SofaScoreHelper;
use Voetbal\ExternalSource\SofaScore\ApiHelper as SofaScoreApiHelper;
use Voetbal\Season as SeasonBase;
use Voetbal\ExternalSource;
use Psr\Log\LoggerInterface;
use Voetbal\Import\Service as ImportService;

class Season extends SofaScoreHelper
{

    public function __construct(
        ExternalSource $externalSource,
        SofaScoreApiHelper $apiHelper,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $externalSource,
            $apiHelper,
            $logger
        );
    }

    /**
     * @return array|SeasonBase[]
     */
    public function get(): array
    {
        $apiData = $this->apiHelper->getData(
            "football//" . $this->apiHelper->getCurrentDateAsString() . "/json",
            ImportService::SEASON_CACHE_MINUTES );
        return $this->getSeasons($apiData->sportItem->tournaments);
    }

    /**
     * @param array $competitions |stdClass[]
     * @return array|SeasonBase[]
     */
    protected function getSeasons(array $competitions): array
    {
        //  {"name":"Premier League 19\/20","slug":"premier-league-1920","year":"19\/20","id":23776}

        $seasons = array();
        foreach ($competitions as $competition) {
            if( $competition->season === null ) {
                continue;
            }
            if( strlen( $competition->season->year ) === 0 ) {
                continue;
            }
            $name = $this->getName( $competition->season->year );
            if( $this->hasName( $seasons, $name ) ) {
                continue;
            }
            // check if name exists, also do check with associations!!!
            $season = new SeasonBase( $name, $this->getPeriod( $name ) );
            $season->setId($name);
            $seasons[$name] = $season;
        }
        return $seasons;
    }

    protected function getName( string $name ): string {
        if( strpos( $name, "/") === false ) {
            return $name;
        }
        return "20" . substr( $name, 0, 2 ) . "/" . "20" . substr( $name, 3, 2 );
    }

    protected function getPeriod( string $name ): Period {
        $start = null;
        $end = null;
        if( strpos( $name, "/") !== false ) {
            $year = substr( $name, 0, 4 );
            $start = $year . "-07-01";
            $year = substr( $name, 5, 4 );
            $end = $year . "-07-01";
        } else {
            $start = $name . "-01-01";
            $end = (((int)$name)+1) . "-01-01";
        }
        $startDateTime = \DateTimeImmutable::createFromFormat ( "Y-m-d\TH:i:s", $start . "T00:00:00", new \DateTimeZone('UTC') );
        $endDateTime = \DateTimeImmutable::createFromFormat ( "Y-m-d\TH:i:s", $end . "T00:00:00", new \DateTimeZone('UTC') );
        return new Period( $startDateTime, $endDateTime );
    }


}