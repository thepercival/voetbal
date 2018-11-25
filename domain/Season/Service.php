<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\Season;

use Voetbal\Season;
use Voetbal\Season\Repository as SeasonRepository;
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
	public function create( string $name, Period $period ): Season
	{
		$seasonWithSameName = $this->repos->findOneBy( array('name' => $name ) );
		if ( $seasonWithSameName !== null ){
			throw new \Exception("het seizoen ".$name." bestaat al", E_ERROR );
		}
		return new Season( $name, $period );;
	}

    /**
     * @param Season $season
     * @param $name
     * @param Period $period
     * @return mixed
     * @throws \Exception
     */
	public function edit( Season $season, $name, Period $period )
	{
        $seasonWithSameName = $this->repos->findOneBy( array('name' => $name ) );
		if ( $seasonWithSameName !== null and $seasonWithSameName !== $season ){
			throw new \Exception("het seizoen ".$name." bestaat al", E_ERROR );
		}
        $season->setName( $name );
        $season->setPeriod( $period );
		return $season;
	}
}