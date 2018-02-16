<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-2-17
 * Time: 21:49
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Competition\Service as CompetitionService;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Competition
{
    /**
     * @var CompetitionService
     */
	protected $service;
    /**
     * @var CompetitionRepository
     */
	protected $repos;
    /**
     * @var Serializer
     */
	protected $serializer;

	public function __construct(CompetitionService $service, CompetitionRepository $repos, Serializer $serializer)
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
		return $response->withStatus(404, 'geen competitie met het opgegeven id gevonden');
	}

	public function add( $request, $response, $args)
	{
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Competition $competitionSer */
            $competitionSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Competition', 'json');
            if ( $competitionSer === null ) {
                throw new \Exception("er kan geen competitie worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }
            $competitionRet = $this->service->create( $competitionSer );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $competitionRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write( $sErrorMessage );
	}

	public function edit( ServerRequestInterface $request, ResponseInterface $response, $args)
	{
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Competition $competitionSer */
            $competitionSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Competition', 'json');
            if ( $competitionSer === null ) {
                throw new \Exception("er kan geen competitie worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }

            $competition = $this->repos->find($competitionSer->getId());
            if ( $competition === null ) {
                throw new \Exception("de competitie kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }

            $competitionRet = $this->service->changeBasics(
                $competition,
                $competitionSer->getName(),
                $competitionSer->getAbbreviation()
            );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $competitionRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write( $sErrorMessage );
	}

	public function remove( $request, $response, $args)
	{
		$competition = $this->repos->find($args['id']);
		$sErrorMessage = null;
		try {
			$this->repos->remove($competition);

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