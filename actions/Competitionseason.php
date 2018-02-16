<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-2-17
 * Time: 21:49
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Competitionseason\Service as CompetitionseasonService;
use Voetbal\Competitionseason\Repository as CompetitionseasonRepository;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\Season\Repository as SeasonRepository;
use Voetbal\Association\Repository as AssociationRepository;

final class Competitionseason
{
    /**
     * @var CompetitionseasonService
     */
    protected $service;
    /**
     * @var CompetitionseasonRepository
     */
	protected $repos;
    /**
     * @var CompetitionRepository
     */
    protected $competitionRepos;
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
        CompetitionseasonService $service,
        CompetitionseasonRepository $repos,
        CompetitionRepository $competitionRepos,
        SeasonRepository $seasonRepos,
        AssociationRepository $associationRepos,
        Serializer $serializer
    )
	{
        $this->service = $service;
		$this->repos = $repos;
        $this->associationRepos = $associationRepos;
        $this->competitionRepos = $competitionRepos;
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
            /** @var \Voetbal\Competitionseason $competitionseasonSer */
            $competitionseasonSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Competitionseason', 'json');
            if ( $competitionseasonSer === null ) {
                throw new \Exception("er kan competitieseizoen worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }
            $association = $this->associationRepos->find( $competitionseasonSer->getAssociation()->getId() );
            if ( $association === null ){
                throw new \Exception("de bond kan niet gevonden worden o.b.v. de invoergegevens", E_ERROR );
            }
            $competition = $this->competitionRepos->find( $competitionseasonSer->getCompetition()->getId() );
            if ( $competition === null ){
                throw new \Exception("de competitie kan niet gevonden worden o.b.v. de invoergegevens", E_ERROR );
            }
            $season = $this->seasonRepos->find( $competitionseasonSer->getSeason()->getId() );
            if ( $season === null ){
                throw new \Exception("het seizoen kan niet gevonden worden o.b.v. de invoergegevens", E_ERROR );
            }
            $competitionseasonSer->setAssociation($association);
            $competitionseasonSer->setCompetition($competition);
            $competitionseasonSer->setSeason($season);
            $competitionseasonRet = $this->service->create( $competitionseasonSer );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $competitionseasonRet, 'json'));
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
            /** @var \Voetbal\Competitionseason $competitionseasonSer */
            $competitionseasonSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Competitionseason', 'json');
            if ( $competitionseasonSer === null ) {
                throw new \Exception("er kan competitieseizoen worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }

            $competitionseason = $this->repos->find($competitionseasonSer->getId());
            if ( $competitionseason === null ) {
                throw new \Exception("het competitieseizoen kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }

            $competitionseasonRet = $this->service->changeStartDateTime( $competitionseason, $competitionseasonSer->getStartDateTime() );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $competitionseasonRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write( $sErrorMessage );
    }

	public function remove( $request, $response, $args)
	{
		$competitionseason = $this->repos->find($args['id']);
		$sErrorMessage = null;
		try {
			$this->service->remove($competitionseason);

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