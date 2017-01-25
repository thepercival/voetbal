<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-1-17
 * Time: 21:56
 */

namespace Voetbal\Tests;

use \Voetbal\Association as Association;

class AssociationTest extends \PHPUnit_Framework_TestCase
{
	public function testCreateNameMin()
	{
		$this->expectException(\InvalidArgumentException::class);
		$associationName = new Association\Name("");
	}

	public function testCreateNameMax()
	{
		$this->expectException(\InvalidArgumentException::class);
		$associationName = new Association\Name("123456789012345678901");
	}

	public function testCreateNameCharactersOne()
	{
		$this->expectException(\InvalidArgumentException::class);
		$associationName = new Association\Name("-");
	}

	public function testCreateNameCharactersTwo()
	{
		$this->expectException(\InvalidArgumentException::class);
		$associationName = new Association\Name("1");
	}

	public function testCreateNameCharactersThree()
	{
		$associationName = new Association\Name("K.N.V.B.");
		$this->assertEquals("K.N.V.B.", $associationName );
	}

    public function testCreateDescriptionMin()
    {
        $this->expectException(\InvalidArgumentException::class);
        $associationDescription = new Association\Description("");
    }

    public function testCreateDescriptionMax()
    {
        $this->expectException(\InvalidArgumentException::class);
        $associationDescription = new Association\Description("123456789012345678901234567890123456789012345678901");
    }

	public function testCreate()
	{
		$associationName = new Association\Name("K.N.V.B.");
		$association = new Association( $associationName );
		$this->assertNotEquals(null, $association);
	}
}