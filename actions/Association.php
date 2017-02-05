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

	public function __construct(AssociationRepository $associationRepository, Serializer $serializer)
	{
		$this->repos = $associationRepository;
		$this->service = new AssociationService( $associationRepository );
		$this->serializer = $serializer;
	}

	public function fetch( $request, $response, $args)
	{
		$associations = $this->repos->findAll();
		return $response
			->withHeader('Content-Type', 'application/json;charset=utf-8')
			->write( $this->serializer->serialize( $associations, 'json') );
		;

	}

	public function fetchOne( $request, $response, $args)
	{
		$association = $this->repos->find($args['id']);
		if ($association) {
			return $response
				->withHeader('Content-Type', 'application/json;charset=utf-8')
				->write($this->serializer->serialize( $association, 'json'));
			;
		}
		return $response->withStatus(404, 'geen bond met het opgegeven id gevonden');
	}

	public function add( $request, $response, $args)
	{
		$name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
		$description = null;
		$descriptionInput = filter_var($request->getParam('description'), FILTER_SANITIZE_STRING);
		if ( $descriptionInput === false ) {
			$description = new Voetbal\Association\Description( $descriptionInput );
		}
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
	}

	public function remove( $request, $response, $args)
	{
		$association = $this->repos->find($args['id']);
		$sErrorMessage = null;
		try {
			$this->service->remove($association);

			return $response
				->withStatus(200);
			;
		}
		catch( \Exception $e ){
			$sErrorMessage = $e->getMessage();
		}
		return $response->withStatus(404, $sErrorMessage );
	}
}