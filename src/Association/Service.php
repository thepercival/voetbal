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
	 * @param Name $name
	 * @param Description $description
	 * @param Association $parent
	 */
	public function create( Association\Name $name, Association\Description $description, Association $parent )
	{

		$association = new Association( $name );
		$association->setDescription($description);
		$association->setParent($parent);

		$this->repos->getEntityManager()->persist($association);
		$this->repos->getEntityManager()->flush();
	}

	/**
	 * @param Association $association
	 * @param Name $name
	 */
	public function changeName( Association $association, Association\Name $name )
	{
		$association->setName($name);

		$this->repos->getEntityManager()->persist($association);
		$this->repos->getEntityManager()->flush();
	}

	/**
	 * @param Association $association
	 * @param Description $description
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