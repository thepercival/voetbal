<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\Season;

use Voetbal\Season;
use Voetbal\Repository\Season as SeasonRepository;
use League\Period\Period;

class Service
{
	/**
	 * @var SeasonRepository
	 */
	protected $repos;

	/**
	 * Service constructor.
	 *
	 * @param SeasonRepository $repos
	 */
	public function __construct( SeasonRepository $repos )
	{
		$this->repos = $repos;
	}

    /**
     * @param $name
     * @param Period $period
     * @return Season
     * @throws \Exception
     */
	public function create( $name, Period $period )
	{
		$season = new Season( $name, $period );

        $seasonWithSameName = $this->repos->findOneBy( array('name' => $name ) );
		if ( $seasonWithSameName !== null ){
			throw new \Exception("het seizoen ".$name." bestaat al", E_ERROR );
		}

		$this->repos->save($season);

		return $season;
	}

    /**
     * @param Season $season
     * @param $name
     * @param Period $period
     * @throws \Exception
     */
	public function edit( Season $season, $name, Period $period )
	{
        $seasonWithSameName = $this->repos->findOneBy( array('name' => $name ) );
		if ( $seasonWithSameName !== null and $seasonWithSameName !== $season ){
			throw new \Exception("het seizoen ".$name." bestaat al", E_ERROR );
		}

        $season = new Season( $name, $period );

		$this->repos->save($season);
	}

    /**
     * @param Season $season
     */
	public function remove( Season $season )
	{
		$this->repos->remove($season);
	}
}