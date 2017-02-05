<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\Association;

use Voetbal\Association;
use Voetbal\Repository\Association as AssociationRepository;

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
	public function create( $name, $description = null, Association $parent = null )
	{
		$association = new Association( $name );
		$association->setDescription($description);
		$association->setParent($parent);

		$associationWithSameName = $this->repos->findOneBy( array('name' => $name ) );
		if ( $associationWithSameName !== null ){
			throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
		}

		$this->repos->save($association);

		return $association;
	}

	/**
	 * @see Association\Service\Contract::changeName()
	 */
	public function changeName( Association $association, $name )
	{
		// @TODO check if name not exists
		/*$association = $this->repos->findOneBy( array('name' => $name ) );
		if ( $association !== null ){
			throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
		}*/

		$association->setName($name);

		$this->repos->save($association);
	}

	/**
	 * @see Association\Service\Contract::changeDescription()
	 */
	public function changeDescription( Association $association, $description )
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

		$this->repos->save($association);
	}

	/**
	 * @param Association $association
	 *
	 * @throws \Exception
	 */
	public function remove( Association $association )
	{
		$this->repos->remove($association);
	}
}