<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:03
 */

namespace Voetbal\Repository;

use \Doctrine\ORM\EntityRepository;

class Main extends EntityRepository
{
	public function save( $object )
	{
		$this->_em->persist($object);
		$this->_em->flush();
		return $object;
	}

	public function remove( $object )
	{
		$this->_em->remove($object);
		$this->_em->flush();
	}
}