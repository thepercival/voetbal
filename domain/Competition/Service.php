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
     * @param Competition $competitionSer
     * @return mixed
     * @throws \Exception
     */
	public function create( League $league, Season $season, \DateTimeImmutable $startDateTime ): Competition
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
        $competition->setStartDateTime( $startDateTime );

        return $this->repos->save($competition);
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

        return $this->repos->save($competition);
	}

    /**
     * @param Competition $competition
     */
    public function publish( Competition $competition )
    {

    }

    /**
     * @param Competition $competition
     */
	public function remove( Competition $competition )
	{
		$this->repos->remove($competition);
	}
}