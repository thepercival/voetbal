<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-2-17
 * Time: 21:49
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\League\Service as LeagueService;
use Voetbal\League\Repository as LeagueRepository;
use Voetbal\Association\Repository as AssociationRepository;
use Voetbal;

final class League
{
    /**
     * @var LeagueService
     */
	protected $service;
    /**
     * @var LeagueRepository
     */
	protected $repos;
    /**
     * @var AssociationRepository
     */
    protected $associationRepos;
    /**
     * @var Serializer
     */
	protected $serializer;

	public function __construct(
	    LeagueService $service,
        LeagueRepository $repos,
        AssociationRepository $associationRepos,
        Serializer $serializer)
	{
        $this->repos = $repos;
		$this->service = $service;
        $this->associationRepos = $associationRepos;
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
		return $response->withStatus(404)->write('geen competitie met het opgegeven id gevonden');
	}

	public function add( $request, $response, $args)
	{
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\League $leagueSer */
            $leagueSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\League', 'json');
            if ( $leagueSer === null ) {
                throw new \Exception("er kan geen competitie worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }

            $association = $this->associationRepos->find($leagueSer->getAssociation()->getId());
            if ( $association === null ) {
                throw new \Exception("de bond kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }

            $leagueRet = $this->service->create(
                $leagueSer->getName(),
                $leagueSer->getSport(),
                $association,
                $leagueSer->getAbbreviation()
            );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $leagueRet, 'json'));
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
            /** @var \Voetbal\League $leagueSer */
            $leagueSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\League', 'json');
            if ( $leagueSer === null ) {
                throw new \Exception("er kan geen competitie worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }

            $league = $this->repos->find($leagueSer->getId());
            if ( $league === null ) {
                throw new \Exception("de competitie kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }

            $leagueRet = $this->service->changeBasics(
                $league,
                $leagueSer->getName(),
                $leagueSer->getAbbreviation()
            );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $leagueRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write( $sErrorMessage );
	}

	public function remove( $request, $response, $args)
	{
		$league = $this->repos->find($args['id']);
		$sErrorMessage = null;
		try {
			$this->repos->remove($league);

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