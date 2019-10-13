<?php

use \JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;

use Voetbal\SerializationHandler\Round as RoundSerializationHandler;
use Voetbal\SerializationHandler\Qualify\Group as QualifyGroupSerializationHandler;
use Voetbal\SerializationHandler\Round\Number as RoundNumberSerializationHandler;
// use Voetbal\SerializationHandler\Config as ConfigSerializationHandler;
use Voetbal\SerializationHandler\Structure as StructureSerializationHandler;

$app->add(function ( $request,  $response, callable $next) use ( $container ){
    $apiVersion = $request->getHeaderLine('HTTP_X_API_VERSION');
    if( strlen( $apiVersion ) === 0 ) {
        $apiVersion = "1";
    }

    $container['serializer'] = function() use ($container, $apiVersion) {
        $settings = $container['settings'];
        $serializerBuilder = SerializerBuilder::create()->setDebug($settings['displayErrorDetails']);
        if( $settings["environment"] === "production") {
            $serializerBuilder = $serializerBuilder->setCacheDir($settings['serializer']['cache_dir']);
        }
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
        foreach( $settings['serializer']['yml_dir'] as $ymlnamespace => $ymldir ){
            $serializerBuilder->addMetadataDir($ymldir,$ymlnamespace);
        }

        $serializerBuilder->configureHandlers(function(JMS\Serializer\Handler\HandlerRegistry $registry) {
            $registry->registerSubscribingHandler(new StructureSerializationHandler());
            $registry->registerSubscribingHandler(new RoundNumberSerializationHandler());
            $registry->registerSubscribingHandler(new RoundSerializationHandler());
//            $registry->registerSubscribingHandler(new QualifyGroupSerializationHandler());
        });
        $serializerBuilder->addDefaultHandlers();

        return $serializerBuilder->build();
    };

    $response = $next($request, $response);
    header_remove("X-Powered-By");
    return $response;
});

//$container["cache"] = function ($container) {
//    return new CacheUtil;
//};



