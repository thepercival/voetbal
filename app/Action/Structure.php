<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-11-17
 * Time: 14:02
 */

namespace Voetbal\App\Action;

use JMS\Serializer\Serializer;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Planning\Service as PlanningService;
use Voetbal\Structure\Repository as StructureRepository;
use Voetbal\Competition\Repository as CompetitionRepository;
use Doctrine\ORM\EntityManager;

final class Structure
{
    /**
     * @var StructureService
     */
    protected $service;
    /**
     * @var StructureRepository
     */
    protected $repos;
    /**
     * @var CompetitionRepository
     */
    protected $competitionRepos;
    /**
     * @var Serializer
     */
    protected $serializer;
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(
        StructureService $service,
        StructureRepository $repos,
        CompetitionRepository $competitionRepos,
        Serializer $serializer,
        EntityManager $em
    )
    {
        $this->service = $service;
        $this->repos = $repos;
        $this->competitionRepos = $competitionRepos;
        $this->serializer = $serializer;
        $this->em = $em;
    }

    public function fetch( $request, $response, $args)
    {
        return $response->withStatus( 400 )->write( "only one entity can be fetched" );

    }

    public function fetchOne( $request, $response, $args)
    {
        $competition = $this->competitionRepos->find( (int) $request->getParam("competitionid") );
        if( $competition === null ) {
            return $response->withStatus(404)->write('geen indeling gevonden voor competitie');
        }

        $structure = $this->service->getStructure( $competition );
        // var_dump($structure); die();

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write($this->serializer->serialize( $structure, 'json'));
        ;
    }


    public function add( $request, $response, $args)
    {
        return $response->withStatus( 401 )->write( "only one entity can be fetched" );

//        $this->em->getConnection()->beginTransaction();
//        try {
//            /** @var \Voetbal\Structure $structureSer */
//            $structureSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Structure', 'json');
//
//            if ( $structureSer === null ) {
//                throw new \Exception("er kan geen structuur worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
//            }
//
//            $competitionid = (int) $request->getParam("competitionid");
//            $competition = $this->competitionRepos->find($competitionid);
//            if ( $competition === null ) {
//                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
//            }
//
//            $roundNumber = $this->repos->findRoundNumber($competition, 1);
//            if( $roundNumber !== null ) {
//                throw new \Exception("er is al een structuur aanwezig", E_ERROR);
//            }
//
//            $structure = $this->service->createFromSerialized( $structureSer, $competition );
//            $this->repos->customPersist($structure);
//            $this->em->getConnection()->commit();
//
//            return $response
//                ->withStatus(201)
//                ->withHeader('Content-Type', 'application/json;charset=utf-8')
//                ->write($this->serializer->serialize( $structure, 'json'));
//            ;
//        }
//        catch( \Exception $e ){
//            $this->em->getConnection()->rollBack();
//            return $response->withStatus( 422 )->write( $e->getMessage() );
//        }
    }

    public function edit( $request, $response, $args)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            /** @var \Voetbal\Structure $structureSer */
            $structureSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Structure', 'json');
            if ( $structureSer === null ) {
                throw new \Exception("er kan geen ronde worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }
            $competition = $this->competitionRepos->find( (int) $args['id'] );
            if ($competition === null) {
                throw new \Exception("er kan geen competitie worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }

            $roundNumberAsValue = 1;
            $this->repos->remove( $competition, $roundNumberAsValue );
            // parentQualifyGroup moet erin voor parent

            // @TODO kijk als in $structureSer wel een childRound staat, zoja kijken waarom deze dan niet opgeslagen wordt

            $structure = $this->service->createFromSerialized( $structureSer, $competition );
            $roundNumber = $this->repos->customPersist($structure, $roundNumberAsValue);

//            $planningService = new PlanningService($competition);
//            $games = $planningService->create( $roundNumber, $competition->getStartDateTime() );
//            foreach( $games as $game ) {
//                $this->em->persist($game);
//            }
//            $this->em->flush();

            $this->em->getConnection()->commit();

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $structure, 'json'));
            ;
        }
        catch( \Exception $e ){
            $this->em->getConnection()->rollBack();
            return $response->withStatus( 401 )->write( $e->getMessage() );
        }
    }

    public function remove( $request, $response, $args)
    {
        try {
            throw new \Exception("er is geen competitie zonder structuur mogelijk", E_ERROR);
        } catch (\Exception $e) {
            return $response->withStatus(404)->write($e->getMessage());
        }
    }
}