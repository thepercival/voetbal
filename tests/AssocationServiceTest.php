<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 21:50
 */

namespace Voetbal\Tests;

use Voetbal\Association as Association;
use Voetbal\Repository\Association as AssociationRepository;

class AssociationServiceTest extends \PHPUnit_Framework_TestCase
{
	public function testCreateNameMin()
	{
		$em = null;
		// $associationRepos = new AssociationRepository( $em );
		// $service = new Association\Service( $associationRepos );
		// $this->expectException(\InvalidArgumentException::class);
		// $associationName = new Association\Name("");
	}
}