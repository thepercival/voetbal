<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace VoetbalApp\Action\Sport;

use JMS\Serializer\Serializer;
use Voetbal\Structure\Repository as StructureRepository;
use Voetbal\Sport\Config\Repository as SportConfigRepository;
use Voetbal\Competition\Repository as CompetitionRepository;

final class PlanningConfig
{
    /**
     * @var StructureRepository
     */
    protected $structureRepos;
    /**
     * @var CompetitionRepository
     */
    protected $competitionRepos;
    /**
     * @var SportConfigRepository
     */
    protected $repos;

    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        SportConfigRepository $repos,
        StructureRepository $structureRepos,
        CompetitionRepository $competitionRepos,
        Serializer $serializer
    )
    {
        $this->repos = $repos;
        $this->structureRepos = $structureRepos;
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
            $structure = $this->structureRepos->getStructure( $competition ); // to init next/previous
            $roundNumber = $structure->getRoundNumber( (int) $request->getParam("roundnumber") );
            if ( $roundNumber === null ) {
                throw new \Exception("het rondenummer kan niet gevonden worden", E_ERROR);
            }

            /** @var \Voetbal\Sport\Config $configSer */
            $sportConfigSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Sport\Config', 'json');
            if ( $sportConfigSer === null ) {
                throw new \Exception("er zijn geen sport-instellingen gevonden o.b.v. de invoergegevens", E_ERROR);
            }

//            $sport = $competition->getSport( (int) $request->getParam("sportid") );
//            $sportConfig = $roundNumber->getSportScoreConfig( $sport );
//            $sportConfig->setWinPoints( $sportConfigSer->getWinPoints() );
//            $sportConfig->setDrawPoints( $sportConfigSer->getDrawPoints() );
//            $sportConfig->setWinPointsExt( $sportConfigSer->getWinPointsExt() );
//            $sportConfig->setDrawPointsExt( $sportConfigSer->getDrawPointsExt() );
//            $sportConfig->setPointsCalculation( $sportConfigSer->getPointsCalculation() );
//            $this->repos->save($sportConfig);
//
//            return $response
//                ->withStatus(201)
//                ->withHeader('Content-Type', 'application/json;charset=utf-8')
//                ->write($this->serializer->serialize( $sportConfig, 'json'));
//            ;
        }
        catch( \Exception $e ){
            return $response->withStatus(422 )->write( $e->getMessage() );
        }
    }
}