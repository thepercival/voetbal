<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:15
 */
namespace Voetbal\Association\Service;

use Voetbal\Association;

interface Contract
{
	public function create( $name, $description = null, Association $parent = null );
	public function changeName( Association $association,$name );
	public function changeDescription( Association $association, $description );
	public function changeParent( Association $association, Association $parent );
	public function remove( Association $association );
}