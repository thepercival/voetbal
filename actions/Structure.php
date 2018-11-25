<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-11-17
 * Time: 14:02
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Round;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Competition;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Round\Repository as RoundRepository;
use Voetbal\Competition\Repository as CompetitionRepository;
use Doctrine\ORM\EntityManager;

//use Voetbal;
//use Psr\Http\Message\ServerRequestInterface;
//use Psr\Http\Message\ResponseInterface;

final class Structure
{
    /**
     * @var StructureService
     */
    protected $service;
    /**
     * @var RoundRepository
     */
    protected $roundRepos;
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
        RoundRepository $repos,
        CompetitionRepository $competitionRepos,
        Serializer $serializer,
        EntityManager $em
    )
    {
        $this->roundRepos = $repos;
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
        $apiVersion = $request->getHeaderLine('X-Api-Version');
        if( $apiVersion !== '2') {
            return $this->fetchOneDeprecated( $request, $response, $args);
        }
        $cs = $this->competitionRepos->find( (int) $request->getParam("competitionid") );
        if( $cs === null ) {
            return $response->withStatus(404)->write('geen indeling gevonden voor competitieseizoen');
        }

        $structure = $this->service->getStructure( $cs );
        // var_dump($structure); die();

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write($this->serializer->serialize( $structure, 'json'));
        ;
    }

    public function fetchOneDeprecated( $request, $response, $args)
    {
        $cs = $this->competitionRepos->find( (int) $request->getParam("competitionid") );
        if( $cs === null ) {
            return $response->withStatus(404)->write('geen indeling gevonden voor competitieseizoen');
        }

        $structure = $this->service->getStructure( $cs );

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write($this->serializer->serialize( $structure->getRootRound(), 'json'));
        ;
    }


    public function add( $request, $response, $args)
    {
        $apiVersion = $request->getHeaderLine('X-Api-Version');
        if( $apiVersion !== '2') {
            return $this->addDeprecated( $request, $response, $args);
        }
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

            $structure = $this->service->createFromSerialized( $structureSer, $competition );

            foreach( $structure->getRoundNumbers() as $roundNumber ) {
                $this->em->persist($roundNumber);
            }
            $this->em->persist($structure->getRootRound());
            $this->em->flush();

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $structure, 'json'));
            ;
        }
        catch( \Exception $e ){
            return $response->withStatus( 422 )->write( $e->getMessage() );
        }
    }

    public function addDeprecated( $request, $response, $args)
    {
        try {
            /** @var \Voetbal\Round $roundSer */
            $roundSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Round', 'json');
            if ( $roundSer === null ) {
                throw new \Exception("er kan geen ronde worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
            }

            $competitionid = (int) $request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionid);
            if ( $competition === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            $structureSer = $this->convertRoundToStructure( $roundSer, $competition );
            $structure = $this->service->createFromSerializedDeprecated( $structureSer, $competition );
            foreach( $structure->getRoundNumbers() as $roundNumber ) {
                $this->em->persist($roundNumber);
            }
            $this->em->persist($structure->getRootRound());
            $this->em->flush();

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $structure->getRootRound(), 'json'));
            ;
        }
        catch( \Exception $e ){
            return $response->withStatus( 422 )->write( $e->getMessage() );
        }
    }

    private function convertRoundToStructure( Round $roundSerialized, Competition $competition ): Structure {

        $firstRoundNumber = $this->getRoundNumberFromRound( $roundSerialized, $competition );
        return new Structure( $firstRoundNumber, $roundSerialized );
    }

    private function getRoundNumberFromRound(
        Round $roundSerialized,
        Competition $competition,
        RoundNumber $previousRoundNumber = null
    ): RoundNumber {

        $refCl = new \ReflectionClass('RoundNumber');
        $roundNumber = null;
        if( $previousRoundNumber !== null && $previousRoundNumber->hasNext() ) {
            $roundNumber = $previousRoundNumber->getNext();
        } else {
            $roundNumber = $refCl->newInstanceWithoutConstructor ();
            $refCl->getProperty("competition")->setValue($competition); // private, through constructor
            $refCl->getProperty("previous")->setValue($previousRoundNumber); // private, through constructor
            $roundNumber->setConfig( $roundSerialized->getConfig() );
            $refCl->getProperty("number")->setValue($roundNumber);
        }
        foreach( $roundSerialized->getChildRounds() as $childRoundSerialized ) {
            $this->getRoundNumberFromRound( $childRoundSerialized, $competition, $roundNumber );
        }

        return $roundNumber;
    }

    private function convertRoundToHelper( Competition $competition, Round $roundSerialized, RoundNumber $roundNumberSerialized = null ): Round
    {
        if( $roundNumberSerialized === null ) {
            $roundNumberSerialized = new RoundNumber($competition);
        }
        $number = $previousRoundNumber === null ? 1 : $previousRoundNumber->getNumber() + 1;
        $roundNumber->setNumber( $number );
        $this->repos->save($roundNumber);
        $this->configService->create($roundNumber, $roundSerialized->getConfig()->getOptions() );
        if( $previousRoundNumber !== null ) {
            $previousRoundNumber->setNext($roundNumber);
        }

        $rootRound = $this->roundService->create(
            $roundNumber,
            $roundSerialized->getWinnersOrLosers(),
            $roundSerialized->getQualifyOrder(),
            $roundSerialized->getPoules()->toArray()
        );

        return $rootRound;
    }

    public function edit( $request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Round $round */
            $roundSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Round', 'json');
            if ( $roundSer === null ) {
                throw new \Exception("er kan geen ronde worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }

            $competitionid = (int) $request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionid);
            if ( $competition === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            $round = $this->service->update( $roundSer, $competition );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $round, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422 )->write( $sErrorMessage );
    }

    public function remove( $request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $round = $this->roundRepos->find($args['id']);
            if ($round === null) {
                throw new \Exception('de te verwijderen indeling kan niet gevonden worden', E_ERROR);
            }
            $competitionId = (int)$request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ($competition === null) {
                throw new \Exception("er kan geen competitie worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }
            if ($round->getCompetition() !== $competition) {
                throw new \Exception("de competitie van de ronde komt niet overeen met de verstuurde competitie",
                    E_ERROR);
            }
            $this->service->remove($round);
            return $response->withStatus(204);
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write($sErrorMessage);
    }
}