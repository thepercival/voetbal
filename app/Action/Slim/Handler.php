<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 22:48
 */

namespace Voetbal\App\Action\Slim;

use Voetbal;
use \Slim\Container as SlimContainer;

class Handler
{
    /**
     * @var SlimContainer
     */
    protected $container;

    public function __construct(SlimContainer $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        // 'logger' should be configured to log
        // $this->container->get('logger')->info("default resource route get : " . $resourceType . ( $id ? '/' . $id : null ) );

        $resourceType = array_key_exists("resourceType", $args) ? $args["resourceType"] : null;
        $action = $this->getAction($resourceType);
        if ($action === null) {
            return $response->withStatus(404)->write('geen actie gevonden voor ' . $resourceType);
        }
        return $this->executeAction($action, $request, $response, $args);
    }

    protected function executeAction($action, $request, $response, $args)
    {
        $id = array_key_exists("id", $args) ? $args["id"] : null;

        if ($request->isGet()) {
            if ($id) {
                $response = $action->fetchOne($request, $response, $args);
            } else {
                $response = $action->fetch($request, $response, $args);
            }
        } elseif ($request->isPost()) {
            $response = $action->add($request, $response, $args);
        } elseif ($request->isPut()) {
            $response = $action->edit($request, $response, $args);
        } elseif ($request->isDelete()) {
            $response = $action->remove($request, $response, $args);
        }

        return $response;
    }

    protected function getAction($resourceType)
    {
        /** @var Voetbal\Service $voetbalservice */
        $voetbalservice = $this->container->get('voetbal');
        $serializer = $this->container->get('serializer');
        $em = $this->container->get('em');

        $action = null;
        if ($resourceType === 'associations') {
            $action = new Voetbal\App\Action\Association(
                $voetbalservice->getService(Voetbal\Association::class),
                $voetbalservice->getRepository(Voetbal\Association::class),
                $serializer);
        } elseif ($resourceType === 'competitors') {
            $action = new Voetbal\App\Action\Competitor(
                $voetbalservice->getRepository(Voetbal\Competitor::class),
                $voetbalservice->getRepository(Voetbal\Association::class),
                $serializer);
        } elseif ($resourceType === 'seasons') {
            $action = new Voetbal\App\Action\Season(
                $voetbalservice->getRepository(Voetbal\Season::class),
                $serializer);
        } elseif ($resourceType === 'leagues') {
            $action = new Voetbal\App\Action\League(
                $voetbalservice->getRepository(Voetbal\League::class),
                $voetbalservice->getRepository(Voetbal\Association::class),
                $serializer);
        } elseif ($resourceType === 'competitions') {
            $action = new Voetbal\App\Action\Competition(
                $voetbalservice->getService(Voetbal\Competition::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $voetbalservice->getRepository(Voetbal\League::class),
                $voetbalservice->getRepository(Voetbal\Season::class),
                $serializer
            );
        }
//        elseif ( $resourceType === 'rounds' ){
//             $action = new Voetbal\App\Action\Old(
//                $voetbalservice->getService(Voetbal\Round::class),
//                $voetbalservice->getRepository(Voetbal\Round::class),
//                $voetbalservice->getRepository(Voetbal\Competition::class),
//                $serializer
//            );
//        }
        elseif ($resourceType === 'games') {
            $action = new Voetbal\App\Action\Game(
                $voetbalservice->getService(Voetbal\Game::class),
                $voetbalservice->getRepository(Voetbal\Game::class),
                $voetbalservice->getRepository(Voetbal\Game\Score::class),
                $voetbalservice->getRepository(Voetbal\Place::class),
                $voetbalservice->getRepository(Voetbal\Poule::class),
                $voetbalservice->getRepository(Voetbal\Field::class),
                $voetbalservice->getRepository(Voetbal\Referee::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer);
        } elseif ($resourceType === 'structures') {
            $action = new Voetbal\App\Action\Structure(
                $voetbalservice->getService(Voetbal\Structure::class),
                $voetbalservice->getStructureRepository(),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer, $em
            );
        } elseif ($resourceType === 'planning') {
            $action = new Voetbal\App\Action\Planning(
                $voetbalservice->getRepository(Voetbal\Game::class),
                $voetbalservice->getService(Voetbal\Game::class),
                $voetbalservice->getRepository(Voetbal\Poule::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer, $em);
        } elseif ($resourceType === 'fields') {
            $action = new Voetbal\App\Action\Field(
                $voetbalservice->getRepository(Voetbal\Field::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer
            );
        } elseif ($resourceType === 'referees') {
            $action = new Voetbal\App\Action\Referee(
                $voetbalservice->getRepository(Voetbal\Referee::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer
            );
        } elseif ($resourceType === 'countconfigs') {
            $action = new Voetbal\App\Action\Sport\CountConfig(
                $voetbalservice->getRepository(Voetbal\Sport\CountConfig::class),
                $voetbalservice->getRepository(Voetbal\Structure::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer
            );
        } elseif ($resourceType === 'planningconfigs') {
            $action = new Voetbal\App\Action\Planning\Config(
                $voetbalservice->getRepository(Voetbal\Planning\Config::class),
                $voetbalservice->getRepository(Voetbal\Structure::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer
            );
        } elseif ($resourceType === 'places') {
            $action = new Voetbal\App\Action\Place(
                $voetbalservice->getRepository(Voetbal\Place::class),
                $voetbalservice->getRepository(Voetbal\Competitor::class),
                $voetbalservice->getRepository(Voetbal\Poule::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer);
        }
        return $action;
    }
}