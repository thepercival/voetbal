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

    public function __construct(\Slim\Container $container)
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
            $action = new Voetbal\Action\Association(
                $voetbalservice->getService(Voetbal\Association::class),
                $voetbalservice->getRepository(Voetbal\Association::class),
                $serializer);
        } elseif ($resourceType === 'teams') {
            $action = new Voetbal\Action\Team(
                $voetbalservice->getService(Voetbal\Team::class),
                $voetbalservice->getRepository(Voetbal\Team::class),
                $voetbalservice->getRepository(Voetbal\Association::class),
                $serializer);
        } elseif ($resourceType === 'seasons') {
            $action = new Voetbal\Action\Season(
                $voetbalservice->getService(Voetbal\Season::class),
                $voetbalservice->getRepository(Voetbal\Season::class),
                $serializer);
        } elseif ($resourceType === 'leagues') {
            $action = new Voetbal\Action\League(
                $voetbalservice->getService(Voetbal\League::class),
                $voetbalservice->getRepository(Voetbal\League::class),
                $voetbalservice->getRepository(Voetbal\Association::class),
                $serializer);
        } elseif ($resourceType === 'competitions') {
            $action = new Voetbal\Action\Competition(
                $voetbalservice->getService(Voetbal\Competition::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $voetbalservice->getRepository(Voetbal\League::class),
                $voetbalservice->getRepository(Voetbal\Season::class),
                $serializer
            );
        }
//        elseif ( $resourceType === 'rounds' ){
//             $action = new Voetbal\Action\Old(
//                $voetbalservice->getService(Voetbal\Round::class),
//                $voetbalservice->getRepository(Voetbal\Round::class),
//                $voetbalservice->getRepository(Voetbal\Competition::class),
//                $serializer
//            );
//        }
        elseif ($resourceType === 'games') {
            $action = new Voetbal\Action\Game(
                $voetbalservice->getService(Voetbal\Game::class),
                $voetbalservice->getRepository(Voetbal\Game::class),
                $voetbalservice->getRepository(Voetbal\PoulePlace::class),
                $voetbalservice->getRepository(Voetbal\Poule::class),
                $voetbalservice->getRepository(Voetbal\Field::class),
                $voetbalservice->getRepository(Voetbal\Referee::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer);
        } elseif ($resourceType === 'structures') {
            $action = new Voetbal\Action\Structure(
                $voetbalservice->getService(Voetbal\Structure::class),
                $voetbalservice->getRepository(Voetbal\Round::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer, $em
            );
        } elseif ($resourceType === 'planning') {
            $action = new Voetbal\Action\Planning(
                $voetbalservice->getService(Voetbal\Planning::class),
                $voetbalservice->getRepository(Voetbal\Game::class),
                $voetbalservice->getService(Voetbal\Game::class),
                $voetbalservice->getRepository(Voetbal\PoulePlace::class),
                $voetbalservice->getRepository(Voetbal\Poule::class),
                $voetbalservice->getRepository(Voetbal\Field::class),
                $voetbalservice->getRepository(Voetbal\Referee::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer);
        } elseif ($resourceType === 'fields') {
            $action = new Voetbal\Action\Field(
                $voetbalservice->getRepository(Voetbal\Field::class),
                $voetbalservice->getService(Voetbal\Field::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer
            );
        } elseif ($resourceType === 'referees') {
            $action = new Voetbal\Action\Referee(
                $voetbalservice->getRepository(Voetbal\Referee::class),
                $voetbalservice->getService(Voetbal\Referee::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer
            );
        } elseif ($resourceType === 'roundconfigs') {
            $action = new Voetbal\Action\Round\Config(
                $voetbalservice->getService(Voetbal\Structure::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer
            );
        } elseif ($resourceType === 'pouleplaces') {
            $action = new Voetbal\Action\PoulePlace(
                $voetbalservice->getRepository(Voetbal\PoulePlace::class),
                $voetbalservice->getService(Voetbal\PoulePlace::class),
                $voetbalservice->getRepository(Voetbal\Team::class),
                $voetbalservice->getRepository(Voetbal\Poule::class),
                $voetbalservice->getRepository(Voetbal\Competition::class),
                $serializer);
        }
        return $action;
    }
}