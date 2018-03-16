<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\League;

use Voetbal\League;
use Voetbal\Association;
use Voetbal\League\Repository as LeagueRepository;

class Service
{
	/**
	 * @var LeagueRepository
	 */
	protected $repos;

	/**
	 * Service constructor.
	 *
	 * @param LeagueRepository $repos
	 */
	public function __construct( LeagueRepository $repos )
	{
		$this->repos = $repos;
	}

    /**
     * @param League $leagueSer
     * @return mixed
     * @throws \Exception
     */
	public function create( string $name, string $sport, Association $association, string $abbreviation = null ): League
	{
		$leagueWithSameName = $this->repos->findOneBy( array('name' => $name ) );
		if ( $leagueWithSameName !== null ){
			throw new \Exception("de league met de naam ".$name." bestaat al", E_ERROR );
		}
        if ( strlen($abbreviation) > 0 ){
            $leagueWithSameAbbreviation = $this->repos->findOneBy( array('abbreviation' => $abbreviation ) );
            if ( $leagueWithSameAbbreviation !== null ){
                throw new \Exception("de league met de afkorting ".$abbreviation." bestaat al", E_ERROR );
            }
        }
        $league = new League( $association, $name );
        $league->setSport( $sport );
        return $this->repos->save($league);
	}

    /**
     * @param League $league
     * @param $name
     * @param $abbreviation
     * @return mixed
     * @throws \Exception
     */
    public function changeBasics( League $league, $name, $abbreviation )
    {
        $leagueWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $leagueWithSameName !== null and $leagueWithSameName !== $league ){
            throw new \Exception("de competitie met de naam ".$name." bestaat al", E_ERROR );
        }

        $league->setName($name);
        $league->setAbbreviation($abbreviation);

        return $this->repos->save($league);
    }
}