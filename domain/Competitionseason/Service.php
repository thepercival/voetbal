<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\Competitionseason;

use Voetbal;
use Voetbal\Competitionseason\Repository as CompetitionseasonRepository;
use Voetbal\Competitionseason;
use Voetbal\Competition;
use Voetbal\Season;
use Voetbal\Association;


class Service
{
	/**
	 * @var CompetitionseasonRepository
	 */
	protected $repos;

	/**
	 * Service constructor.
	 *
	 * @param CompetitionseasonRepository $repos
	 */
	public function __construct( CompetitionseasonRepository $repos )
	{
		$this->repos = $repos;
	}

    /**
     * @param Competitionseason $competitionseasonSer
     * @return mixed
     * @throws \Exception
     */
	public function create( CompetitionSeason $competitionseasonSer )
	{
		$sameCompetitionseason = $this->repos->findOneBy( array(
            'competition' => $competitionseasonSer->getCompetition(),
            'season' => $competitionseasonSer->getSeason()
        ) );

        if ( $sameCompetitionseason !== null ){
            throw new \Exception("het competitieseizoen bestaat al", E_ERROR );
        }

        if( !$competitionseasonSer->getSeason()->getPeriod()->contains( $competitionseasonSer->getStartDateTime() ) ) {
            throw new \Exception("de startdatum van het competiteseizoen valt buiten het seizoen", E_ERROR );
        }

        return $this->repos->save($competitionseasonSer);
	}

    /**
     * @param Competitionseason $competitionseason
     * @param \DateTimeImmutable $startDateTime
     * @return mixed
     * @throws \Exception
     */
	public function changeStartDateTime( Competitionseason $competitionseason, \DateTimeImmutable $startDateTime )
	{
        if( $competitionseason->getState() === Competitionseason::STATE_PUBLISHED ) {
            throw new \Exception("het competitieseizoen kan niet worden gewijzigd, omdat deze al is gepubliceerd", E_ERROR );
        }

        if( !$competitionseason->getSeason()->getPeriod()->contains( $startDateTime ) ) {
            throw new \Exception("de startdatum van het competiteseizoen valt buiten het seizoen", E_ERROR );
        }

        $competitionseason->setStartDateTime( $startDateTime );

        return $this->repos->save($competitionseason);
	}

    /**
     * @param Competitionseason $competitionseason
     */
    public function publish( Competitionseason $competitionseason )
    {

    }

    /**
     * @param Competitionseason $competitionseason
     */
	public function remove( Competitionseason $competitionseason )
	{
		$this->repos->remove($competitionseason);
	}
}