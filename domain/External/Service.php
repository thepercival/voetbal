<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\External;

use Voetbal\External\Object;

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
     * @param Importable $importable
     * @param System $externalSystem
     * @param $externalId
     * @return \Voetbal\External\Object
     */
	public function create( Importable $importable, System $externalSystem, $externalId )
	{
	    // make an external from the importable and save to the repos

		$externalobject = new \Voetbal\External\Object(
            $importable, $externalSystem, $externalId
        );

		// check if not exisys
        /*$leagueWithSameName = $this->repos->findOneBy( array('name' => $name ) );
		if ( $leagueWithSameName !== null ){
			throw new \Exception("de competitie ".$name." bestaat al", E_ERROR );
		}*/

		$this->repos->save($externalobject);

		return $externalobject;
	}

    /**
     * @param League $league
     */
	public function remove( \Voetbal\External\Object $externalobject )
	{
		$this->repos->remove($externalobject);
	}
}