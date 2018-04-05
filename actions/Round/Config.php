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

    public function add( $request, $response, $args )
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
            $roundNumber = (int) $request->getParam("roundnumber");
            // $roundNumber = 1;
            if ( $roundNumber === 0 ) {
                throw new \Exception("geen rondenummer opgegeven", E_ERROR);
            }
            $this->structureService->setConfigs( $competition, $roundNumber, $configSer );

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
}