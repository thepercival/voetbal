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
use Voetbal\State;

class Service
{


	/**
	 * Service constructor.
	 *
	 * @param CompetitionRepository $repos
	 */
	public function __construct()
	{

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
        if( $competition->getState() > State::Created ) {
            throw new \Exception("de competitie kan niet worden gewijzigd, omdat deze al gespeelde wedstrijden heeft", E_ERROR );
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
        if( $competition->getState() > State::Created ) {
            throw new \Exception("de competitie kan niet worden gewijzigd, omdat deze al gespeelde wedstrijden heeft", E_ERROR );
        }

        $competition->setRuleSet( $ruleSet );

        return $competition;
    }
}