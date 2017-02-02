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
	public function create( Association\Name $name, Association\Description $description, Association $parent );
	public function changeName( Association $association, Association\Name $name );
	public function changeDescription( Association $association, Association\Description $description );
	public function changeParent( Association $association, Association $parent );
	public function remove( Association $association );
}