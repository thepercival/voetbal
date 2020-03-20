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
use Voetbal\ExternalSource\Sport as ExternalSourceSport;
use Voetbal\Sport as SportBase;
use Voetbal\ExternalSource\SofaScore;
use Psr\Log\LoggerInterface;
use Voetbal\Import\Service as ImportService;

class Sport extends SofaScoreHelper implements ExternalSourceSport
{
    /**
     * @var array|SportBase[]|null
     */
    protected $sports;
    /**
     * @var SportBase
     */
    protected $defaultSport;

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

    public function getSports(): array
    {
        if( $this->sports !== null ) {
            return $this->sports;
        }
        $this->sports = [];

        $apiData = $this->apiHelper->getData(
            "event/count/by-sports/json",
            ImportService::SPORT_CACHE_MINUTES );

        $sportsData = [];
        if ( is_object( $apiData ) ) {
            $sportsData = get_object_vars( $apiData );
        }

        $this->setSports($sportsData);
        $this->sports = array_values( $this->sports );
        return $this->sports;
    }

    public function getSport( $id = null ): ?SportBase
    {
        $sports = $this->getSports();
        if( array_key_exists( $id, $sports ) ) {
            return $sports[$id];
        }
        return null;
    }

    protected function setSports(array $externalSourceSports)
    {
        foreach ($externalSourceSports as $sportName => $value) {
            if( $this->hasName( $this->sports, $sportName ) ) {
                continue;
            }
            $sport = $this->createSport( $sportName ) ;
            $this->sports[$sport->getId()] = $sport;
        }
    }

    protected function createSport( string $name ): SportBase
    {
        $sport = new SportBase($name);
        $sport->setTeam(false);
        $sport->setId($name);
        return $sport;
    }
}