<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\Association;

class Service implements \Voetbal\Association\Service\Contract
{
	/**
	 * @var \Voetbal\Repository\Association
	 */
	protected $repos;
	/**
	 * Service constructor.
	 */
	public function __construct( \Voetbal\Repository\Association $associationRepos )
	{
		$this->repos = $associationRepos;
	}

	public function add(   )
	{

		/*
		$association = new \Voetbal\Association();
		$user->setEmail($email);
		$user->setName($name);
		$user->setPassword( password_hash( $password, PASSWORD_DEFAULT) );
		$user->setActive($active);
		*/

		$this->repos->getEntityManager()->persist($association);
		$this->repos->getEntityManager()->flush();
	}


}