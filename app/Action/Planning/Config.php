<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace Voetbal\Appx\Action\Planning;

use JMS\Serializer\Serializer;
use Voetbal\Structure\Repository as StructureRepository;
use Voetbal\Planning\Config\Repository as PlanningConfigRepository;
use Voetbal\Competition\Repository as CompetitionRepository;

final class Config
{
    /**
     * @var PlanningConfigRepository
     */
    protected $repos;
    /**
     * @var StructureRepository
     */
    protected $structureRepos;
    /**
     * @var CompetitionRepository
     */
    protected $competitionRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        PlanningConfigRepository $repos,
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

            /** @var \Voetbal\Planning\Config $configSer */
            $planningConfigSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Planning\Config', 'json');
            if ( $planningConfigSer === null ) {
                throw new \Exception("er zijn geen plannings-instellingen gevonden o.b.v. de invoergegevens", E_ERROR);
            }

            $planningConfig = $roundNumber->getPlanningConfig();
            $planningConfig->setNrOfHeadtoheadMatches( $planningConfigSer->getNrOfHeadtoheadMatches() );
            $planningConfig->setHasExtension( $planningConfigSer->getHasExtension() );
            $planningConfig->setMinutesPerGameExt( $planningConfigSer->getMinutesPerGameExt() );
            $planningConfig->setEnableTime( $planningConfigSer->getEnableTime() );
            $planningConfig->setMinutesPerGame( $planningConfigSer->getMinutesPerGame() );
            $planningConfig->setMinutesBetweenGames( $planningConfigSer->getMinutesBetweenGames() );
            $planningConfig->setMinutesAfter( $planningConfigSer->getMinutesAfter() );
            $planningConfig->setTeamup( $planningConfigSer->getTeamup() );
            $planningConfig->setSelfReferee( $planningConfigSer->getSelfReferee() );

            $this->repos->save($planningConfig);

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $planningConfig, 'json'));
            ;
        }
        catch( \Exception $e ){
            return $response->withStatus(422 )->write( $e->getMessage() );
        }
    }
}