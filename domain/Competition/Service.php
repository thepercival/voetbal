<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\Competition;

use Voetbal;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\Competition;
use Voetbal\League;
use Voetbal\Season;

class Service
{
	/**
	 * @var CompetitionRepository
	 */
	protected $repos;

	/**
	 * Service constructor.
	 *
	 * @param CompetitionRepository $repos
	 */
	public function __construct( CompetitionRepository $repos )
	{
		$this->repos = $repos;
	}

    /**
     * @param League $league
     * @param Season $season
     * @param int $ruleSet
     * @param \DateTimeImmutable $startDateTime
     * @return Competition
     * @throws \Exception
     */
	public function create( League $league, Season $season, int $ruleSet, \DateTimeImmutable $startDateTime ): Competition
	{
		$sameCompetition = $this->repos->findOneBy( array(
            'league' => $league,
            'season' => $season
        ) );

        if ( $sameCompetition !== null ){
            throw new \Exception("de competitie bestaat al", E_ERROR );
        }

        if( !$season->getPeriod()->contains( $startDateTime ) ) {
            throw new \Exception("de startdatum van de competitie valt buiten het seizoen", E_ERROR );
        }

        $competition = new Competition( $league, $season );
        $competition->setRuleSet($ruleSet);
        $competition->setStartDateTime( $startDateTime );

        return $competition;
	}

    /**
     * @param Competition $competition
     * @param \DateTimeImmutable $startDateTime
     * @return mixed
     * @throws \Exception
     */
	public function changeStartDateTime( Competition $competition, \DateTimeImmutable $startDateTime )
	{
        if( $competition->getState() === Competition::STATE_PUBLISHED ) {
            throw new \Exception("de competitie kan niet worden gewijzigd, omdat deze al is gepubliceerd", E_ERROR );
        }

        if( !$competition->getSeason()->getPeriod()->contains( $startDateTime ) ) {
            throw new \Exception("de startdatum van de competitie valt buiten het seizoen", E_ERROR );
        }

        $competition->setStartDateTime( $startDateTime );

        return $competition;
	}

    /**
     * @param Competition $competition
     * @param int $ruleSet
     * @return Competition
     * @throws \Exception
     */
    public function changeRuleSet( Competition $competition, int $ruleSet )
    {
        if( $competition->getState() === Competition::STATE_PUBLISHED ) {
            throw new \Exception("de competitie kan niet worden gewijzigd, omdat deze al is gepubliceerd", E_ERROR );
        }

        $competition->setRuleSet( $ruleSet );

        return $competition;
    }

    /**
     * @param Competition $competition
     */
    public function publish( Competition $competition )
    {

    }
}