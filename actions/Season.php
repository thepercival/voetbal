<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-2-17
 * Time: 21:49
 */

namespace Voetbal\Action;

use Symfony\Component\Serializer\Serializer;
use Voetbal\Season\Service as SeasonService;
use Voetbal\Season\Repository as SeasonRepository;
use Voetbal;
use League\Period\Period;

final class Season
{
	protected $service;
	protected $repos;
	protected $serializer;

	public function __construct(SeasonService $service, SeasonRepository $repos, Serializer $serializer)
	{
		$this->repos = $repos;
		$this->service = $service;
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
		return $response->withStatus(404, 'geen seizoen met het opgegeven id gevonden');
	}

	public function add( $request, $response, $args)
	{
		$name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
        $startdate = $request->getParam('startdate');
        $enddate = $request->getParam('enddate');
		$sErrorMessage = null;
		try {
			$season = $this->service->create(
				$name,
				new Period( $startdate, $enddate )
			);

			return $response
				->withStatus(201)
				->withHeader('Content-Type', 'application/json;charset=utf-8')
				->write($this->serializer->serialize( $season, 'json'));
			;
		}
		catch( \Exception $e ){
			$sErrorMessage = $e->getMessage();
		}
		return $response->withStatus(404, $sErrorMessage );
	}

	public function edit( $request, $response, $args)
	{
		$season = $this->repos->find($args['id']);
		if ( $season === null ) {
			throw new \Exception("de aan te passen bond kan niet gevonden worden",E_ERROR);
		}

		$name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
        $startdate = $request->getParam('startdate');
        $enddate = $request->getParam('enddate');

		$sErrorMessage = null;
		try {
			$season = $this->service->edit( $season, $name, new Period( $startdate, $enddate ) );

			return $response
				->withStatus(201)
				->withHeader('Content-Type', 'application/json;charset=utf-8')
				->write($this->serializer->serialize( $season, 'json'));
			;
		}
		catch( \Exception $e ){
			$sErrorMessage = $e->getMessage();
		}
		return $response->withStatus(404, $sErrorMessage );
	}

	public function remove( $request, $response, $args)
	{
		$season = $this->repos->find($args['id']);
		$sErrorMessage = null;
		try {
			$this->service->remove($season);

			return $response
				->withStatus(201);
			;
		}
		catch( \Exception $e ){
			$sErrorMessage = $e->getMessage();
		}
		return $response->withStatus(404, $sErrorMessage );
	}
}