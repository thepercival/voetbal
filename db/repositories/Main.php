<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:03
 */

namespace VoetbalRepository;

use Doctrine\ORM\EntityManager;

abstract class Main extends \Doctrine\ORM\EntityRepository
{

	public function save( $object )
	{
		$this->_em->persist($object);
		$this->_em->flush();
		return $object;
	}
}