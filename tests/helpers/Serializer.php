<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-1-19
 * Time: 11:58
 */

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\ContextFactory\CallableSerializationContextFactory;
use JMS\Serializer\ContextFactory\CallableDeserializationContextFactory;
use JMS\Serializer\GraphNavigatorInterface;
use Voetbal\Game;
use JMS\Serializer\Handler\HandlerRegistry;

use Voetbal\SerializationHandler\Round as RoundSerializationHandler;
use Voetbal\SerializationHandler\Qualify\Group as QualifyGroupSerializationHandler;
use Voetbal\SerializationHandler\Round\Number as RoundNumberSerializationHandler;
use Voetbal\SerializationHandler\Config as ConfigSerializationHandler;
use Voetbal\SerializationHandler\Structure as StructureSerializationHandler;

function getSerializer(): \JMS\Serializer\Serializer
{
    $apiVersion = 2;

    $serializerBuilder = SerializerBuilder::create()->setDebug(true);

    $serializerBuilder->setPropertyNamingStrategy(new \JMS\Serializer\Naming\SerializedNameAnnotationStrategy(new \JMS\Serializer\Naming\IdenticalPropertyNamingStrategy()));

    $serializerBuilder->setSerializationContextFactory(function () use ($apiVersion) {
        return SerializationContext::create()
            ->setGroups(array('Default'))
            ->setVersion($apiVersion);
    });
    $serializerBuilder->setDeserializationContextFactory(function () use ($apiVersion) {
        return DeserializationContext::create()
            ->setGroups(array('Default'))
            ->setVersion($apiVersion);
    });
    $serializerBuilder->addMetadataDir(__DIR__.'/../../serialization/yml', 'Voetbal');

    $serializerBuilder->configureHandlers(function (JMS\Serializer\Handler\HandlerRegistry $registry) {
        $registry->registerSubscribingHandler(new StructureSerializationHandler());
        $registry->registerSubscribingHandler(new RoundNumberSerializationHandler());
        $registry->registerSubscribingHandler(new RoundSerializationHandler());
        $registry->registerSubscribingHandler(new QualifyGroupSerializationHandler());
    });

    $serializerBuilder->addDefaultHandlers();

    /*$serializerBuilder
        ->configureListeners(function(JMS\Serializer\EventDispatcher\EventDispatcher $dispatcher) {
//            $dispatcher->addListener('serializer.pre_serialize',
//                function(JMS\Serializer\EventDispatcher\PreSerializeEvent $event) {
//                    // do something
//                }
//            );
//
//            $dispatcher->addSubscriber(new MyEventSubscriber());
        })
    ;*/

    /*$serializerBuilder->addDefaultHandlers();
    $serializerBuilder
        ->configureHandlers(function(JMS\Serializer\Handler\HandlerRegistry $registry) {
            $registry->registerHandler(GraphNavigatorInterface::DIRECTION_DESERIALIZATION, 'Voetbal\PoulePlace', 'json',
                function($visitor, $gameData, $type, $context) {
                    if( $visitor->getCurrentObject() instanceof Game ) {
                        // var_dump($context);
                        $s = $context;
                        $poulePlaces = $visitor->getCurrentObject()->getPoule()->getPlaces();
                        $homePoulePlaces = array_filter( $poulePlaces->toArray() , function ( $poulePlace ) use ($gameData) {
                            return $poulePlace->getNumber() === $gameData["homePoulePlace"]["number"];
                        });
                        return reset( $homePoulePlaces );
                    }

                }
            );
        })
    ;*/

    return $serializerBuilder->build();
}
