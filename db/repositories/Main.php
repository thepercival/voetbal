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
        try{
            $this->_em->persist($object);
            $this->_em->flush();

        }
        catch( \Exception $e ) {

            throw new \Exception($e->getMessage(), E_ERROR );
        }

		return $object;
	}

	public function remove( $object )
	{
		$this->_em->remove($object);
		$this->_em->flush();
	}
}