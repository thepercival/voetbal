<?php

namespace Voetbal\Repository;

/**
 * Association
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Association extends \Doctrine\ORM\EntityRepository
{
	public function getByName( $arrWhere )
	{
		return $this->findOneBy( $arrWhere );

		/*$dql = "SELECT b, e, r FROM Bug b JOIN b.engineer e JOIN b.reporter r ORDER BY b.created DESC";
		$query = $this->getEntityManager()->createQuery($dql);
		return $query->getResult();*/
	}
}
