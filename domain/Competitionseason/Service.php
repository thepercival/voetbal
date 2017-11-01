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
     * @param Competition $competition
     * @param Season $season
     * @param Association $association
     * @return Competition|Competitionseason
     * @throws \Exception
     */
	public function create( Association $association, Competition $competition, Season $season, \DateTimeImmutable $startDate )
	{
		// check if competitionseason with same competition and season exists
        $sameCompetitionseason = $this->repos->findOneBy( array('competition' => $competition->getId(), 'season' => $season->getId()  ) );
		if ( $sameCompetitionseason !== null ){
			throw new \Exception("het competitieseizoen bestaat al", E_ERROR );
		}

        $competitionseason = new Competitionseason( $competition, $season, $association  );
        $competitionseason->setStartDateTime( $startDate );

        try {
            return $this->repos->save($competitionseason);
        }
        catch( \Exception $e ){
            throw new \Exception(urlencode($e->getMessage()), E_ERROR );
        }
	}

    /**
     * @param Competitionseason $competitionseason
     * @param Association $association
     * @param $qualificationrule
     * @throws \Exception
     */
	public function edit( Competitionseason $competitionseason, Association $association, $qualificationrule, $sport = null )
	{
        if( $competitionseason->getState() === Competitionseason::STATE_PUBLISHED ) {
            throw new \Exception("het competitieseizoen kan niet worden gewijzigd, omdat deze al is gepubliceerd", E_ERROR );
        }

        $competitionseason->setAssociation($association);
        $competitionseason->setQualificationRule($qualificationrule);
        $competitionseason->setSport($sport);

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