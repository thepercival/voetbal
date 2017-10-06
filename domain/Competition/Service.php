<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\Competition;

use Voetbal\Competition;
use Voetbal\Competition\Repository as CompetitionRepository;

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
     * @param $name
     * @param null $abbreviation
     * @return Competition
     * @throws \Exception
     */
	public function create( $name, $abbreviation = null )
	{
		$competition = new Competition( $name, $abbreviation );

        $competitionWithSameName = $this->repos->findOneBy( array('name' => $name ) );
		if ( $competitionWithSameName !== null ){
			throw new \Exception("de competitie met de naam ".$name." bestaat al", E_ERROR );
		}
        if ( strlen($abbreviation) > 0 ){
            $competitionWithSameAbbreviation = $this->repos->findOneBy( array('abbreviation' => $abbreviation ) );
            if ( $competitionWithSameAbbreviation !== null ){
                throw new \Exception("de competitie met de afkorting ".$abbreviation." bestaat al", E_ERROR );
            }
        }
        return $this->repos->save($competition);
	}

    /**
     * @param Competition $competition
     * @param $name
     * @param null $abbreviation
     * @throws \Exception
     */
	public function edit( Competition $competition, $name, $abbreviation = null )
	{
        $competitionWithSameName = $this->repos->findOneBy( array('name' => $name ) );
		if ( $competitionWithSameName !== null and $competitionWithSameName !== $competition ){
			throw new \Exception("de competitie met de naam ".$name." bestaat al", E_ERROR );
		}
		if ( strlen($abbreviation) > 0 ){
            $competitionWithSameAbbreviation = $this->repos->findOneBy( array('abbreviation' => $abbreviation ) );
            if ( $competitionWithSameAbbreviation !== null and $competitionWithSameAbbreviation !== $competition ){
                throw new \Exception("de competitie met de afkorting ".$abbreviation." bestaat al", E_ERROR );
            }
        }
        $competition->setName($name);
        $competition->setAbbreviation($abbreviation);

		return $this->repos->save($competition);
	}

    /**
     * @param Competition $competition
     */
	public function remove( Competition $competition )
	{
		$this->repos->remove($competition);
	}
}