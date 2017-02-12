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
	public function create( \Voetbal\Importable $importable, System $externalsystem, $externalid )
	{
	    // make an external from the importable and save to the repos

		$externalobject = new \Voetbal\External\Object(
            $importable, $externalsystem, $externalid
        );

		// check if not exisys
        /*$competitionWithSameName = $this->repos->findOneBy( array('name' => $name ) );
		if ( $competitionWithSameName !== null ){
			throw new \Exception("de competitie ".$name." bestaat al", E_ERROR );
		}*/

		$this->repos->save($externalobject);

		return $externalobject;
	}

    /**
     * @param Competition $competition
     */
	public function remove( \Voetbal\External\Object $externalobject )
	{
		$this->repos->remove($externalobject);
	}
}