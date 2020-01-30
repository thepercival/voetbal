<?php


namespace Voetbal\SerializationHandler\Round;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Voetbal\Round\Number as RoundNumber;
use Voetbal\Sport\ScoreConfig as SportScoreConfig;

class NumberEvent implements \JMS\Serializer\EventDispatcher\EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event' => 'serializer.pre_serialize',
                'method' => 'onPreSerialize',
                'class' => 'Voetbal\Round\Number', // if no class, subscribe to every serialization
                'format' => 'json', // optional format
                'priority' => 0, // optional priority
            )
        );
    }

    public function onPreSerialize(\JMS\Serializer\EventDispatcher\PreSerializeEvent $event)
    {
        /** @var RoundNumber $roundNumber */
        $roundNumber = $event->getObject();

        $roundNumber->setSportScoreConfigs(
            $roundNumber->getSportScoreConfigs()->filter( function ( SportScoreConfig $config ) {
                return $config->isFirst();
            } )
        );
    }
}