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
use Voetbal\League\Repository as LeagueRepository;
use Voetbal\Season\Repository as SeasonRepository;
use Voetbal\Association\Repository as AssociationRepository;

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
     * @var LeagueRepository
     */
    protected $leagueRepos;
    /**
     * @var SeasonRepository
     */
    protected $seasonRepos;
    /**
     * @var AssociationRepository
     */
    protected $associationRepos;
    /**
     * @var Serializer
     */
	protected $serializer;

	public function __construct(
        CompetitionService $service,
        CompetitionRepository $repos,
        LeagueRepository $leagueRepos,
        SeasonRepository $seasonRepos,
        AssociationRepository $associationRepos,
        Serializer $serializer
    )
	{
        $this->service = $service;
		$this->repos = $repos;
        $this->associationRepos = $associationRepos;
        $this->leagueRepos = $leagueRepos;
        $this->seasonRepos = $seasonRepos;
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
		return $response->withStatus(404, 'geen competitieseizoen met het opgegeven id gevonden');
	}


	public function add($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $leagueid = (int) $request->getParam("leagueid");
            $league = $this->leagueRepos->find($leagueid);
            if ( $league === null ) {
                throw new \Exception("er kan geen league worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }

            $seasonid = (int) $request->getParam("seasonid");
            $season = $this->associationRepos->find($seasonid);
            if ( $season === null ) {
                throw new \Exception("er kan geen competitie worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }

            /** @var \Voetbal\Competition $competitionSer */
            $competitionSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Competition', 'json');
            if ( $competitionSer === null ) {
                throw new \Exception("er kan competitie worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }

            $competitionSer->setLeague($league);
            $competitionSer->setSeason($season);
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

    public function edit( $request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Competition $competitionSer */
            $competitionSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Competition', 'json');
            if ( $competitionSer === null ) {
                throw new \Exception("er kan competitieseizoen worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }

            $competition = $this->repos->find($competitionSer->getId());
            if ( $competition === null ) {
                throw new \Exception("het competitieseizoen kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }

            $competitionRet = $this->service->changeStartDateTime( $competition, $competitionSer->getStartDateTime() );

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
			$this->service->remove($competition);

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