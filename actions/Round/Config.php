<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace Voetbal\Action\Round;

use JMS\Serializer\Serializer;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\Round\Number\Repository as RoundNumberRepository;

final class Config
{
    /**
     * @var StructureService
     */
    protected $structureService;
    /**
     * @var CompetitionRepository
     */
    protected $competitionRepos;
    /**
     * @var RoundNumberRepository
     */
    protected $roundNumberRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        StructureService $structureService,
        CompetitionRepository $competitionRepos,
        RoundNumberRepository $roundNumberRepos,
        Serializer $serializer
    )
    {
        $this->structureService = $structureService;
        $this->competitionRepos = $competitionRepos;
        $this->roundNumberRepos = $roundNumberRepos;
        $this->serializer = $serializer;
    }

    public function add( $request, $response, $args )
    {
        return $this->addDeprecated($request, $response, $args);
    }

    public function addDeprecated( $request, $response, $args )
    {
        $sErrorMessage = null;

        try {
            $competitionId = (int) $request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ( $competition === null ) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }
            /** @var \Voetbal\Round\Config $configSer */
            $configSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Round\Config', 'json');
            if ( $configSer === null ) {
                throw new \Exception("er kunnen geen ronde-instellingen worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }
            $roundNumberAsValue = (int) $request->getParam("roundnumber");
            if ( $roundNumberAsValue === 0 ) {
                throw new \Exception("geen rondenummer opgegeven", E_ERROR);
            }
            $structure = $this->structureService->getStructure( $competition );
            $roundNumber = $structure->getRoundNumber( $roundNumberAsValue );
            $this->structureService->setConfigs( $roundNumber, $configSer, true );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( true, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422 )->write( $sErrorMessage );
    }

    public function edit( $request, $response, $args )
    {
        $sErrorMessage = null;

        try {
            $competitionId = (int) $request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ( $competition === null ) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }
            $roundNumberId = (int) $request->getParam("roundnumberid");
            $roundNumber = $this->roundNumberRepos->find($roundNumberId);
            if ( $roundNumber === null ) {
                throw new \Exception("het rondenummer kan niet gevonden worden", E_ERROR);
            }
            if ( $roundNumber->getCompetition() !== $competition ) {
                throw new \Exception("de competitie van het rondenummer is niet gelijk aan de opgegeven competitie", E_ERROR);
            }

            /** @var \Voetbal\Round\Config $configSer */
            $configSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Round\Config', 'json');
            if ( $configSer === null ) {
                throw new \Exception("er kunnen geen ronde-instellingen worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }
            // $structure = $this->structureService->getStructure( $competition ); // to init next/previous
            $this->structureService->setConfigs( $roundNumber, $configSer, false );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $roundNumber->getConfig(), 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422 )->write( $sErrorMessage );
    }
}