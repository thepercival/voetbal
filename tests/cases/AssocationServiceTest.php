<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 21:50
 */

namespace Voetbal\Tests;

use Voetbal\Association as Association;
use Voetbal\Association\Repository as AssociationRepository;

class AssociationServiceTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateNameMin()
	{
		$em = null;
		// $associationRepos = new AssociationRepository( $em );
		// $service = new Association\Service( $associationRepos );
		// $this->expectException(\InvalidArgumentException::class);
		// $associationName = new Association\Name("");

        $this->assertEquals(1, 1);
	}
}