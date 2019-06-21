<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace Voetbal\App\Action\Sport;

use JMS\Serializer\Serializer;
use Voetbal\Structure\Repository as StructureRepository;
use Voetbal\Sport\CountConfig\Repository as CountConfigRepository;
use Voetbal\Competition\Repository as CompetitionRepository;

final class CountConfig
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
     * @var CountConfigRepository
     */
    protected $repos;

    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        CountConfigRepository $repos,
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

            /** @var \Voetbal\Sport\CountConfig $configSer */
            $countConfigSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Sport\CountConfig', 'json');
            if ( $countConfigSer === null ) {
                throw new \Exception("er zijn geen sport-instellingen gevonden o.b.v. de invoergegevens", E_ERROR);
            }

            $sport = $competition->getSport( (int) $request->getParam("sportid") );
            $countConfig = $roundNumber->getCountConfig( $sport );
            $countConfig->setQualifyRule( $countConfigSer->getQualifyRule() );
            $countConfig->setWinPoints( $countConfigSer->getWinPoints() );
            $countConfig->setDrawPoints( $countConfigSer->getDrawPoints() );
            $countConfig->setWinPointsExt( $countConfigSer->getWinPointsExt() );
            $countConfig->setDrawPointsExt( $countConfigSer->getDrawPointsExt() );
            $countConfig->setPointsCalculation( $countConfigSer->getPointsCalculation() );
            $this->repos->save($countConfig);

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $countConfig, 'json'));
            ;
        }
        catch( \Exception $e ){
            return $response->withStatus(422 )->write( $e->getMessage() );
        }
    }
}