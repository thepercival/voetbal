<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-2-17
 * Time: 21:49
 */

namespace Voetbal\Action;

use Symfony\Component\Serializer\Serializer;
use Voetbal\Competitionseason\Service as CompetitionseasonService;
use Voetbal\Competitionseason\Repository as CompetitionseasonRepository;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\Season\Repository as SeasonRepository;
use Voetbal\Association\Repository as AssociationRepository;

final class Competitionseason
{
	protected $service;
	protected $repos;
    protected $competitionRepos;
    protected $seasonRepos;
    protected $associationRepos;
	protected $serializer;

	public function __construct(
        CompetitionseasonRepository $repos,
        CompetitionRepository $competitionRepos,
        SeasonRepository $seasonRepos,
        AssociationRepository $associationRepos,
        Serializer $serializer
    )
	{
		$this->repos = $repos;
        $this->associationRepos = $associationRepos;
        $this->competitionRepos = $competitionRepos;
        $this->seasonRepos = $seasonRepos;
		$this->service = new CompetitionseasonService( $repos );
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

	public function add( $request, $response, $args)
	{
        $competition = $this->competitionRepos->find($request->getParam('competitionid'));
        $season = $this->seasonRepos->find($request->getParam('seasonid'));
        $association = $this->associationRepos->find($request->getParam('associationid'));

		$sErrorMessage = null;
		try {
            if ( $competition === null ){
                throw new \Exception("de competitie is niet gevonden", E_ERROR );
            }
            if ( $season === null ){
                throw new \Exception("het seizoen is niet gevonden", E_ERROR );
            }
            if ( $association === null ){
                throw new \Exception("de bond is niet gevonden", E_ERROR );
            }

			$competitionseason = $this->service->create(
                $association,
                $competition,
                $season
			);
            $qualificationrule = filter_var($request->getParam('qualificationrule'), FILTER_VALIDATE_INT);
            if ( $qualificationrule !== false ){
                $competitionseason->setQualificationrule( $qualificationrule );
            }

			return $response
				->withStatus(201)
				->withHeader('Content-Type', 'application/json;charset=utf-8')
				->write($this->serializer->serialize( $competitionseason, 'json'));
			;
		}
		catch( \Exception $e ){
			$sErrorMessage = $e->getMessage();
		}
		return $response->withStatus(404, $sErrorMessage );
	}

	public function edit( $request, $response, $args)
	{
		$competitionseason = $this->repos->find($args['id']);
        $association = $this->associationRepos->find($request->getParam('associationid'));
        $qualificationrule = filter_var($request->getParam('qualificationrule'), FILTER_VALIDATE_INT);

        $sErrorMessage = null;
		try {
            if ( $competitionseason === null ) {
                throw new \Exception("het aan te passen competitieseizoen kan niet gevonden worden",E_ERROR);
            }
            if ( $association === null ){
                throw new \Exception("de te wijzigen bond is niet gevonden", E_ERROR );
            }
            if ( $qualificationrule === false ){
                throw new \Exception("de te wijzigen kwalificatieregel is niet correct", E_ERROR );
            }

			$competitionseason = $this->service->edit( $competitionseason, $association, $qualificationrule );

			return $response
				->withStatus(201)
				->withHeader('Content-Type', 'application/json;charset=utf-8')
				->write($this->serializer->serialize( $competitionseason, 'json'));
			;
		}
		catch( \Exception $e ){
			$sErrorMessage = $e->getMessage();
		}
		return $response->withStatus(404, $sErrorMessage );
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