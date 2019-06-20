<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace Voetbal\App\Action;

use JMS\Serializer\Serializer;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Config\Service as ConfigService;
use Voetbal\Competition\Repository as CompetitionRepository;

final class CountConfig
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
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        StructureService $structureService,
        CompetitionRepository $competitionRepos,
        Serializer $serializer
    )
    {
        $this->structureService = $structureService;
        $this->competitionRepos = $competitionRepos;
        $this->serializer = $serializer;
    }

//    public function add( $request, $response, $args )
//    {
//        return $this->addDeprecated($request, $response, $args);
//    }
//
//    public function addDeprecated( $request, $response, $args )
//    {
//        try {
//            $competitionId = (int) $request->getParam("competitionid");
//            $competition = $this->competitionRepos->find($competitionId);
//            if ( $competition === null ) {
//                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
//            }
//            /** @var \Voetbal\Config $configSer */
//            $configSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Config', 'json');
//            if ( $configSer === null ) {
//                throw new \Exception("er kunnen geen ronde-instellingen worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
//            }
//            $roundNumberAsValue = (int) $request->getParam("roundnumber");
//            if ( $roundNumberAsValue === 0 ) {
//                throw new \Exception("geen rondenummer opgegeven", E_ERROR);
//            }
//            $structure = $this->structureService->getStructure( $competition );
//            $roundNumber = $structure->getRoundNumber( $roundNumberAsValue );
//            $this->configService->updateFromSerialized( $roundNumber, $configSer, true );
//
//            return $response
//                ->withStatus(201)
//                ->withHeader('Content-Type', 'application/json;charset=utf-8')
//                ->write($this->serializer->serialize( true, 'json'));
//            ;
//        }
//        catch( \Exception $e ){
//            return $response->withStatus(422 )->write( $e->getMessage() );
//        }
//    }

    public function edit( $request, $response, $args )
    {
        try {
            $competitionId = (int) $request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ( $competition === null ) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }
            $structure = $this->structureService->getStructure( $competition ); // to init next/previous
            $roundNumber = $structure->getRoundNumberById( (int) $request->getParam("roundnumberid") );
            if ( $roundNumber === null ) {
                throw new \Exception("het rondenummer kan niet gevonden worden", E_ERROR);
            }
            if ( $roundNumber->getCompetition() !== $competition ) {
                throw new \Exception("de competitie van het rondenummer is niet gelijk aan de opgegeven competitie", E_ERROR);
            }

            /** @var \Voetbal\Dep $configSer */
            $configSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Dep', 'json');
            if ( $configSer === null ) {
                throw new \Exception("er kunnen geen ronde-instellingen worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }

            $this->configService->updateFromSerialized( $roundNumber, $configSer, false );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $roundNumber->getConfig(), 'json'));
            ;
        }
        catch( \Exception $e ){
            return $response->withStatus(422 )->write( $e->getMessage() );
        }
    }
}