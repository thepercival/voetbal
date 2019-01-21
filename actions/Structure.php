<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-11-17
 * Time: 14:02
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Structure\Service as StructureService;
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
        $this->repos = $repos;
        $this->competitionRepos = $competitionRepos;
        $this->service = $service;
        $this->serializer = $serializer;
        $this->em = $em;
    }

    public function fetch( $request, $response, $args)
    {
        return $this->fetchOne( $request, $response, $args);

    }

    /**
     * Vanaf api 2.0 structure retourneren, anders rounds
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
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
        $this->em->getConnection()->beginTransaction();
        try {
            /** @var \Voetbal\Structure $structureSer */
            $structureSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Structure', 'json');

            if ( $structureSer === null ) {
                throw new \Exception("er kan geen structuur worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
            }

            $competitionid = (int) $request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionid);
            if ( $competition === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            $roundNumberAsValue = 1;
            $roundNumber = $this->repos->findOneBy(array("competition" => $competition, "number" => $roundNumberAsValue));
            if( $roundNumber ) {
                throw new \Exception("er is al een structuur aanwezig", E_ERROR);
            }

            $structure = $this->service->createFromSerialized( $structureSer, $competition );
            $this->repos->customPersist($structure);
            $this->em->getConnection()->commit();

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $structure, 'json'));
            ;
        }
        catch( \Exception $e ){
            $this->em->getConnection()->rollBack();
            return $response->withStatus( 422 )->write( $e->getMessage() );
        }
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

            $roundNumber = 1;
            $this->repos->remove( $competition, $roundNumber );
            $structure = $this->service->createFromSerialized( $structureSer, $competition );
            $this->repos->customPersist($structure, $roundNumber);

            $this->em->getConnection()->commit();

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $structure, 'json'));
            ;
        }
        catch( \Exception $e ){
            $this->em->getConnection()->rollBack();
            return $response->withStatus(422 )->write( $e->getMessage() );
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