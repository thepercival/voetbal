<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\Competition;

use Voetbal\Competition;
use Voetbal\Repository\Competition as CompetitionRepository;

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
			throw new \Exception("de competitie ".$name." bestaat al", E_ERROR );
		}

		$this->repos->save($competition);

		return $competition;
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
			throw new \Exception("de competitie ".$name." bestaat al", E_ERROR );
		}

        $competition->setName($name);
        $competition->setAbbreviation($abbreviation);

		$this->repos->save($competition);
	}

    /**
     * @param Competition $competition
     */
	public function remove( Competition $competition )
	{
		$this->repos->remove($competition);
	}
}