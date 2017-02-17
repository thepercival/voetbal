<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-2-17
 * Time: 21:49
 */

namespace Voetbal\Action;

use Symfony\Component\Serializer\Serializer;
use Voetbal\Association\Service as AssociationService;
use Voetbal\Repository\Association as AssociationRepository;
use Voetbal;

final class Association
{
	protected $service;
	protected $repos;
	protected $serializer;

	public function __construct(AssociationRepository $repos, Serializer $serializer)
	{
		$this->repos = $repos;
		$this->service = new AssociationService( $repos );
		$this->serializer = $serializer;
	}

	public function fetch( $request, $response, $args)
	{
		$objects = $this->repos->findAll();
		return $response
			->withHeader('Content-Type', 'application/json;charset=utf-8')
			->write( $this->serializer->serialize( $objects, 'json') );
		;

	}

	public function fetchOne( $request, $response, $args)
	{
        $object = $this->repos->find($args['id']);
		if ($object) {
			return $response
				->withHeader('Content-Type', 'application/json;charset=utf-8')
				->write($this->serializer->serialize( $object, 'json'));
			;
		}
		return $response->withStatus(404, 'geen bond met het opgegeven id gevonden');
	}

	public function add( $request, $response, $args)
	{
		$name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
		$description = filter_var($request->getParam('description'), FILTER_SANITIZE_STRING);
		$parentid = filter_var($request->getParam('parentid'),FILTER_SANITIZE_NUMBER_INT);

		$sErrorMessage = null;
		try {
			$association = $this->service->create(
				$name,
				$description,
				$this->repos->find( $parentid )
			);

			return $response
				->withStatus(201)
				->withHeader('Content-Type', 'application/json;charset=utf-8')
				->write($this->serializer->serialize( $association, 'json'));
			;
		}
		catch( \Exception $e ){
			$sErrorMessage = $e->getMessage();
		}
		return $response->withStatus(404, $sErrorMessage );
	}

	public function edit( $request, $response, $args)
	{
		$association = $this->repos->find($args['id']);
		if ( $association === null ) {
			throw new \Exception("de aan te passen bond kan niet gevonden worden",E_ERROR);
		}
		$name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
        $description = filter_var($request->getParam('description'), FILTER_SANITIZE_STRING);
        $parent = filter_var($request->getParam('parentid'),FILTER_SANITIZE_NUMBER_INT,array('flags' => FILTER_NULL_ON_FAILURE));
        if( strlen($parent) === 0 ) { $parent = null; }

		$sErrorMessage = null;
		try {
			$association = $this->service->edit( $association, $name, $description, $parent );

			return $response
				->withStatus(201)
				->withHeader('Content-Type', 'application/json;charset=utf-8')
				->write($this->serializer->serialize( $association, 'json'));
			;
		}
		catch( \Exception $e ){
			$sErrorMessage = $e->getMessage();
		}
		return $response->withStatus(400, $sErrorMessage );
	}

	public function remove( $request, $response, $args)
	{
		$association = $this->repos->find($args['id']);
		$sErrorMessage = null;
		try {
			$this->service->remove($association);

			return $response
				->withStatus(204);
			;
		}
		catch( \Exception $e ){
			$sErrorMessage = $e->getMessage();
		}
		return $response->withStatus(404, $sErrorMessage );
	}
}