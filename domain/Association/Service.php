<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\Association;

use Voetbal\Association;
use VoetbalRepository\Association as AssociationRepository;

class Service implements Association\Service\Contract
{
	/**
	 * @var AssociationRepository
	 */
	protected $repos;

	/**
	 * Service constructor.
	 *
	 * @param AssociationRepository $associationRepos
	 */
	public function __construct( AssociationRepository $associationRepos )
	{
		$this->repos = $associationRepos;
	}

	/**
	 * @see Association\Service\Contract::create()
	 */
	public function create( Association\Name $name, Association\Description $description, Association $parent )
	{
		$association = new Association( $name );
		$association->setDescription($description);
		$association->setParent($parent);

		$association = $this->repos->findOneBy( array('name' => $name ) );
		if ( $association !== null ){
			throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
		}

		$this->repos->getEntityManager()->persist($association);
		$this->repos->getEntityManager()->flush();
	}

	/**
	 * @see Association\Service\Contract::changeName()
	 */
	public function changeName( Association $association, Association\Name $name )
	{
		// @TODO check if name not exists
		/*$association = $this->repos->findOneBy( array('name' => $name ) );
		if ( $association !== null ){
			throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
		}*/

		$association->setName($name);

		$this->repos->getEntityManager()->persist($association);
		$this->repos->getEntityManager()->flush();
	}

	/**
	 * @see Association\Service\Contract::changeDescription()
	 */
	public function changeDescription( Association $association, Association\Description $description )
	{
		$association->setDescription($description);

		$this->repos->getEntityManager()->persist($association);
		$this->repos->getEntityManager()->flush();
	}

	/**
	 * @param Association $association
	 * @param Association $parent
	 */
	public function changeParent( Association $association, Association $parent )
	{
		// within setparent also change child of
		$association->setParent($parent);

		$this->repos->getEntityManager()->persist($association);
		$this->repos->getEntityManager()->flush();
	}

	/**
	 * @param Association $association
	 *
	 * @throws \Exception
	 */
	public function remove( Association $association )
	{
		throw new \Exception("implement", E_ERROR );
	}
}