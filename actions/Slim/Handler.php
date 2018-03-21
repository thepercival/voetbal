<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 22:48
 */

namespace Voetbal\Action\Slim;

use Voetbal;

class Handler
{
    protected $container;

    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        // 'logger' should be configured to log
        // $this->container->get('logger')->info("default resource route get : " . $resourceType . ( $id ? '/' . $id : null ) );

        $resourceType = array_key_exists("resourceType",$args) ? $args["resourceType"] : null;
        $action = $this->getAction( $resourceType );
        if ( $action === null ) {
            return $response->withStatus(404, 'geen actie gevonden voor '.$resourceType);
        }
        return $this->executeAction($action, $request, $response, $args);
    }

    protected function executeAction($action, $request, $response, $args)
    {
        $id = array_key_exists("id",$args) ? $args["id"] : null;

        if ( $request->isGet() ) {
            if ( $id ){
                $response = $action->fetchOne( $request, $response, $args );
            }
            else {
                $response = $action->fetch( $request, $response, $args );
            }
        }
        elseif ( $request->isPost() ) {
            $response = $action->add( $request, $response, $args );
        }
        elseif ( $request->isPut() ) {
            $response = $action->edit( $request, $response, $args );
        }
        elseif ( $request->isDelete() ) {
            $response = $action->remove( $request, $response, $args );
        }

        return $response;
    }

    protected function getAction( $resourceType )
    {
        /** @var Voetbal\Service $voetbalservice */
        $voetbalservice = $this->container->get('voetbal');
        $serializer = $this->container->get('serializer');

        $action = null;
        if ( $resourceType === 'associations' ){
            $repos = $voetbalservice->getRepository(Voetbal\Association::class);
            $service = $voetbalservice->getService( Voetbal\Association::class);
            $action = new Voetbal\Action\Association($service, $repos, $serializer);
        }
        elseif ( $resourceType === 'teams' ){
            $repos = $voetbalservice->getRepository(Voetbal\Team::class);
            $associationRepos = $voetbalservice->getRepository(Voetbal\Association::class);
            $service = $voetbalservice->getService( Voetbal\Team::class);
            $action = new Voetbal\Action\Team($service, $repos, $associationRepos, $serializer);
        }
        elseif ( $resourceType === 'seasons' ){
            $repos = $voetbalservice->getRepository(Voetbal\Season::class);
            $service = $voetbalservice->getService( Voetbal\Season::class);
            $action = new Voetbal\Action\Season($service, $repos, $serializer);
        }
        elseif ( $resourceType === 'leagues' ){
            $repos = $voetbalservice->getRepository(Voetbal\League::class);
            $service = $voetbalservice->getService( Voetbal\League::class);
            $associationRepos = $voetbalservice->getRepository(Voetbal\Association::class);
            $action = new Voetbal\Action\League($service, $repos, $associationRepos, $serializer);
        }
        elseif ( $resourceType === 'competitions' ){
            $repos = $voetbalservice->getRepository(Voetbal\Competition::class);
            $service = $voetbalservice->getService( Voetbal\Competition::class);
            $leagueRepos = $voetbalservice->getRepository(Voetbal\League::class);
            $seasonRepos = $voetbalservice->getRepository(Voetbal\Season::class);
            $action = new Voetbal\Action\Competition(
                $service,
                $repos,
                $leagueRepos,
                $seasonRepos,
                $serializer
            );
        }
//        elseif ( $resourceType === 'rounds' ){
//            $repos = $voetbalservice->getRepository(Voetbal\Round::class);
//            $competitionRepos = $voetbalservice->getRepository(Voetbal\Competition::class);
//            $service = $voetbalservice->getService(Voetbal\Round::class);
//            $action = new Voetbal\Action\Old(
//                $service,
//                $repos,
//                $competitionRepos,
//                $serializer
//            );
//        }
        elseif ( $resourceType === 'games' ){
            $repos = $voetbalservice->getRepository(Voetbal\Game::class);
            $poulePlaceRepos = $voetbalservice->getRepository(Voetbal\PoulePlace::class);
            $service = $voetbalservice->getService(Voetbal\Game::class);
            $pouleRepos = $voetbalservice->getRepository(Voetbal\Poule::class);
            $fieldRepos = $voetbalservice->getRepository(Voetbal\Field::class);
            $refereeRepos = $voetbalservice->getRepository(Voetbal\Referee::class);
            $action = new Voetbal\Action\Game($service, $repos,
                $poulePlaceRepos, $pouleRepos,
                $fieldRepos, $refereeRepos,
                $serializer);
        }
        elseif ( $resourceType === 'structures' ){
            $competitionRepos = $voetbalservice->getRepository(Voetbal\Competition::class);
            $roundRepos = $voetbalservice->getRepository(Voetbal\Round::class);
            $service = $voetbalservice->getService(Voetbal\Structure::class);
            $action = new Voetbal\Action\Structure(
                $service,
                $roundRepos,
                $competitionRepos,
                $serializer
            );
        }
        elseif ( $resourceType === 'planning' ){
            $repos = $voetbalservice->getRepository(Voetbal\Game::class);
            $poulePlaceRepos = $voetbalservice->getRepository(Voetbal\PoulePlace::class);
            $service = $voetbalservice->getService(Voetbal\Planning::class);
            $gameService = $voetbalservice->getService(Voetbal\Game::class);
            $pouleRepos = $voetbalservice->getRepository(Voetbal\Poule::class);
            $fieldRepos = $voetbalservice->getRepository(Voetbal\Field::class);
            $refereeRepos = $voetbalservice->getRepository(Voetbal\Referee::class);
            $action = new Voetbal\Action\Planning($service, $repos, $gameService,
                $poulePlaceRepos, $pouleRepos,
                $fieldRepos, $refereeRepos,
                $serializer);
        }
        elseif ( $resourceType === 'fields' ) {
            $fieldRepos = $voetbalservice->getRepository(Voetbal\Field::class);
            $competitionRepos = $voetbalservice->getRepository(Voetbal\Competition::class);
            $action = new Voetbal\Action\Field(
                $fieldRepos,
                $competitionRepos,
                $serializer
            );
        }
        elseif ( $resourceType === 'roundconfigs' ) {
            $structureService = $voetbalservice->getService(Voetbal\Structure::class);
            $competitionRepos = $voetbalservice->getRepository(Voetbal\Competition::class);
            $action = new Voetbal\Action\Round\Config(
                $structureService,
                $competitionRepos,
                $serializer
            );
        }
        elseif ( $resourceType === 'referees' ) {
            $refereeRepos = $voetbalservice->getRepository(Voetbal\Referee::class);
            $csRepos = $voetbalservice->getRepository(Voetbal\Competition::class);
            $action = new Voetbal\Action\Referee(
                $refereeRepos,
                $csRepos,
                $serializer
            );
        }
        elseif ( $resourceType === 'pouleplaces' ){
            $repos = $voetbalservice->getRepository(Voetbal\PoulePlace::class);
            $service = $voetbalservice->getService(Voetbal\PoulePlace::class);
            $teamRepos = $voetbalservice->getRepository(Voetbal\Team::class);
            $action = new Voetbal\Action\PoulePlace($repos, $service, $teamRepos, $serializer);
        }

        return $action;
    }
}