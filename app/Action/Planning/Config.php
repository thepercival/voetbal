<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace VoetbalApp\Action\Planning;

use JMS\Serializer\Serializer;
use Voetbal\Structure\Repository as StructureRepository;
use Voetbal\Planning\Config\Repository as PlanningConfigRepository;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\Planning\Config as PlanningConfig;

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
    public function add( $request, $response, $args )
    {
        try {
            $competitionId = (int) $request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ( $competition === null ) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }
            /** @var \Voetbal\Planning\Config|false $planningConfigSer */
            $planningConfigSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Planning\Config', 'json');
            if ( $planningConfigSer === false ) {
                throw new \Exception("er kunnen geen plannings-instellingen worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }
            $roundNumberAsValue = (int) $request->getParam("roundnumber");
            if ( $roundNumberAsValue === 0 ) {
                throw new \Exception("geen rondenummer opgegeven", E_ERROR);
            }
            $structure = $this->structureRepos->getStructure( $competition );
            $roundNumber = $structure->getRoundNumber( $roundNumberAsValue );
            if ( $roundNumber === null ) {
                throw new \Exception("geen rondenummer gevonden", E_ERROR);
            }
            if ( $roundNumber->getPlanningConfig() !== null ) {
                throw new \Exception("er is al een planningconfiguratie aanwezig", E_ERROR);
            }


            // $this->rremov($roundNumber->getNext());

            $planningConfig = new PlanningConfig( $roundNumber );
            $planningConfig->setHasExtension( $planningConfigSer->getHasExtension() );
            $planningConfig->setMinutesPerGame( $planningConfigSer->getMinutesPerGame() );
            $planningConfig->setMinutesPerGameExt( $planningConfigSer->getMinutesPerGameExt() );
            $planningConfig->setEnableTime( $planningConfigSer->getEnableTime() );
            $planningConfig->setMinutesBetweenGames( $planningConfigSer->getMinutesBetweenGames() );
            $planningConfig->setMinutesAfter( $planningConfigSer->getMinutesAfter() );
            $planningConfig->setSelfReferee( $planningConfigSer->getSelfReferee() );
            $planningConfig->setTeamup( $planningConfigSer->getTeamup() );
            $planningConfig->setNrOfHeadtohead( $planningConfigSer->getNrOfHeadtohead() );

            // het verwijderen van planningconfig gebeurd vanuit het roundnumber
            $this->repos->save($planningConfig);

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( true, 'json'));
            ;
        }
        catch( \Exception $e ){
            return $response->withStatus(422 )->write( $e->getMessage() );
        }
    }
//
//    private function save( PlanningConfig $planningConfig ) {
//        $this->repos->save($planningConfig);
//        while ( $roundNumber->hasNext() ) {
//            $roundNumber = $roundNumber->getNext()
//        }
//    }
//    while( $roundNumber->hasNext() ) {
//
//}
//remove previous planning config


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

            /** @var \Voetbal\Planning\Config|false $planningConfigSer */
            $planningConfigSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Planning\Config', 'json');
            if ( $planningConfigSer === false ) {
                throw new \Exception("er zijn geen plannings-instellingen gevonden o.b.v. de invoergegevens", E_ERROR);
            }
            $planningConfig = $roundNumber->getPlanningConfig();
            if ( $planningConfig === null ) {
                throw new \Exception("er zijn geen plannings-instellingen gevonden om te wijzigen", E_ERROR);
            }

            $planningConfig->setHasExtension( $planningConfigSer->getHasExtension() );
            $planningConfig->setMinutesPerGameExt( $planningConfigSer->getMinutesPerGameExt() );
            $planningConfig->setEnableTime( $planningConfigSer->getEnableTime() );
            $planningConfig->setMinutesPerGame( $planningConfigSer->getMinutesPerGame() );
            $planningConfig->setMinutesBetweenGames( $planningConfigSer->getMinutesBetweenGames() );
            $planningConfig->setMinutesAfter( $planningConfigSer->getMinutesAfter() );
            $planningConfig->setTeamup( $planningConfigSer->getTeamup() );
            $planningConfig->setSelfReferee( $planningConfigSer->getSelfReferee() );
            $planningConfig->setNrOfHeadtohead( $planningConfigSer->getNrOfHeadtohead() );

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