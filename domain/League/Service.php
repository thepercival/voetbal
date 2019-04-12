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
     * @param string $name
     * @param string $sport
     * @param Association $association
     * @param string|null $abbreviation
     * @return League
     */
	public function create( string $name, string $sport, Association $association, string $abbreviation = null ): League
	{
		$league = new League( $association, $name );
        $league->setSport( $sport );
        return $league;
	}

    /**
     * @param League $league
     * @param string $name
     * @param string $abbreviation
     * @return League
     */
    public function changeBasics( League $league, $name, $abbreviation )
    {
        $league->setName($name);
        $league->setAbbreviation($abbreviation);
        return $league;
    }
}