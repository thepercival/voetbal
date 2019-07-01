<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace VoetbalApp\Action\Sport;

use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\Sport\Config\Repository as SportConfigRepository;
use Voetbal\Sport\Repository as SportRepository;
use JMS\Serializer\Serializer;
use Voetbal\Sport\CustomId as SportCustomId;
use Voetbal\Sport\Config as SportConfig;

final class Config
{
    /**
     * @var CompetitionRepository
     */
    protected $competitionRepos;
    /**
     * @var SportConfigRepository
     */
    protected $repos;
    /**
     * @var SportRepository
     */
    protected $sportRepos;

    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        CompetitionRepository $competitionRepos,
        SportConfigRepository $repos,
        SportRepository $sportRepos,
        Serializer $serializer
    )
    {
        $this->competitionRepos = $competitionRepos;
        $this->repos = $repos;
        $this->sportRepos = $sportRepos;
        $this->serializer = $serializer;
    }

    public function fetch( $request, $response, $args)
    {
        $competitionId = (int) $request->getParam("competitionid");
        $competition = $this->competitionRepos->find($competitionId);
        $params = [];
        if ( $competition !== null ) {
            $params = ["competition" => $competition];
        }
        $objects = $this->repos->findBy($params);
        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write( $this->serializer->serialize( $objects, 'json') );
        ;
    }

    public function fetchOne( $request, $response, $args)
    {
        $object = $this->repos->find($args['id']);
        if ($object) {
            return $response
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $object, 'json'));
            ;
        }
        return $response->withStatus(404)->write('geen sportconfiguratie met het opgegeven id gevonden');
    }

    public function add( $request, $response, $args )
    {
        try {
            $competitionId = (int) $request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ( $competition === null ) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }
            /** @var \Voetbal\Sport\Config $sportConfigSer */
            $sportConfigSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Sport\Config', 'json');
            if ( $sportConfigSer === null ) {
                throw new \Exception("er kunnen geen sportconfiguratie-instellingen worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }
            $sport = $this->sportRepos->find( $sportConfigSer->getSportIdSer() );
            if ( $sport === null ) {
                throw new \Exception("de sport van de configuratie kan niet gevonden worden", E_ERROR);
            }
            if ( $competition->getSportConfigBySport( $sport ) !== null ) {
                throw new \Exception("de sport wordt al gebruikt binnen de competitie", E_ERROR);
            }

            $sportConfig = new SportConfig( $sport, $competition );
            $sportConfig->setWinPoints( $sportConfigSer->getWinPoints() );
            $sportConfig->setDrawPoints( $sportConfigSer->getDrawPoints() );
            $sportConfig->setWinPointsExt( $sportConfigSer->getWinPointsExt() );
            $sportConfig->setDrawPointsExt( $sportConfigSer->getDrawPointsExt() );
            $sportConfig->setPointsCalculation( $sportConfigSer->getPointsCalculation() );
            $sportConfig->setNrOfGameCompetitors( $sportConfigSer->getNrOfGameCompetitors() );
            $this->repos->save($sportConfig);

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $sportConfig, 'json'));
            ;
        }
        catch( \Exception $e ){
            return $response->withStatus(422 )->write( $e->getMessage() );
        }
    }

    public function edit( $request, $response, $args )
    {
        try {
            $competitionId = (int) $request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ( $competition === null ) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }

            /** @var \Voetbal\Sport\Config $sportConfigSer */
            $sportConfigSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Sport\Config', 'json');
            if ( $sportConfigSer === null ) {
                throw new \Exception("er zijn geen sport-instellingen gevonden o.b.v. de invoergegevens", E_ERROR);
            }
            $sport = $this->sportRepos->find( $sportConfigSer->getSportIdSer() );
            if ( $sport === null ) {
                throw new \Exception("de sport van de configuratie kan niet gevonden worden", E_ERROR);
            }
            $sportConfig = $competition->getSportConfigBySport( $sport );
            if( $sportConfig === null ) {
                throw new \Exception("de sportconfig is niet gevonden bij de competitie", E_ERROR);
            }
            $sportConfig->setWinPoints( $sportConfigSer->getWinPoints() );
            $sportConfig->setDrawPoints( $sportConfigSer->getDrawPoints() );
            $sportConfig->setWinPointsExt( $sportConfigSer->getWinPointsExt() );
            $sportConfig->setDrawPointsExt( $sportConfigSer->getDrawPointsExt() );
            $sportConfig->setPointsCalculation( $sportConfigSer->getPointsCalculation() );
            $sportConfig->setNrOfGameCompetitors( $sportConfigSer->getNrOfGameCompetitors() );
            $this->repos->save($sportConfig);

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $sportConfig, 'json'));
            ;
        }
        catch( \Exception $e ){
            return $response->withStatus(401)->write( $e->getMessage() );
        }
    }

    public function remove( $request, $response, $args)
    {
        try {
            $competitionId = (int) $request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ( $competition === null ) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }
            $sportConfig = $this->repos->find($args['id']);
            if( $sportConfig === null ) {
                throw new \Exception("de sportconfig is niet gevonden", E_ERROR);
            }
            if( $competition->getSportConfigByName( $sportConfig ) === null ) {
                throw new \Exception("de sport is niet gevonden bij de competitie", E_ERROR);
            }
            $this->repos->customRemove($sportConfig, $this->sportRepos);
            return $response->withStatus(204);
        }
        catch( \Exception $e ){
            return $response->withStatus(404)->write( $e->getMessage() );
        }
    }
}