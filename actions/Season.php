<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-2-17
 * Time: 21:49
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Season\Service as SeasonService;
use Voetbal\Season\Repository as SeasonRepository;
use Voetbal;
use League\Period\Period;

final class Season
{
    /**
     * @var SeasonService
     */
	protected $service;
    /**
     * @var SeasonRepository
     */
	protected $repos;
    /**
     * @var Serializer
     */
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
		return $response->withStatus(404)->write('geen seizoen met het opgegeven id gevonden');
	}

	public function add( $request, $response, $args)
	{
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Season $seasonSer */
            $seasonSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Season', 'json');

            if ( $seasonSer === null ) {
                throw new \Exception("er kan geen seizoen worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }

            $seasonWithSameName = $this->repos->findOneBy( array('name' => $seasonSer->getName() ) );
            if ( $seasonWithSameName !== null ){
                throw new \Exception("het seizoen ".$seasonSer->getName()." bestaat al", E_ERROR );
            }

            $seasonRet = $this->repos->save( $seasonSer );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $seasonRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write( $sErrorMessage );
	}

	public function edit( $request, $response, $args)
	{
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Season $seasonSer */
            $seasonSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Season', 'json');

            if ( $seasonSer === null ) {
                throw new \Exception("er kan geen seizoen worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }

            $season = $this->repos->find($seasonSer->getId());
            if ( $season === null ) {
                throw new \Exception("de naam van het seizoen wordt al gebruikt", E_ERROR);
            }

            $seasonWithSameName = $this->repos->findOneBy( array( 'name' => $seasonSer->getName() ) );
            if ( $seasonWithSameName !== null and $season->getId() !== $seasonWithSameName->getId() ){
                throw new \Exception("het seizoen ".$seasonSer->getName()." bestaat al", E_ERROR );
            }

            $season->setName( $seasonSer->getName() );
            $season->setStartDateTime( $seasonSer->getStartDateTime() );
            $season->setEndDateTime( $seasonSer->getEndDateTime() );
            $seasonRet = $this->repos->save( $season );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $seasonRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write( $sErrorMessage );
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