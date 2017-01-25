<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:15
 */
namespace Voetbal\Association\Service;

interface Contract
{
	public function create( \Voetbal\Association\Name $name, \Voetbal\Association\Description $description, \Voetbal\Association $parent );
	public function changeName( \Voetbal\Association $association, \Voetbal\Association\Name $name );
	public function changeDescription( \Voetbal\Association $association, \Voetbal\Association\Description $description );
	public function changeParent( \Voetbal\Association $association, \Voetbal\Association $parent );
	public function remove( \Voetbal\Association $association );
}