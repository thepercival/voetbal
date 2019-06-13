<?php

use FCToernooi\Token;
use Gofabian\Negotiation\NegotiationMiddleware;
use Tuupola\Middleware\JwtAuthentication;
use Tuupola\Middleware\CorsMiddleware;
use App\Response\Unauthorized;
use App\Middleware\Authentication;
use \JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use FCToernooi\Auth\JWT\TournamentRule;

$app->add(function ( $request,  $response, callable $next) use ( $container ){
    $apiVersion = $request->getHeaderLine('X-Api-Version');
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
        return $serializerBuilder->build();
    };

    $response = $next($request, $response);
    header_remove("X-Powered-By");
    return $response;
});

//$container["cache"] = function ($container) {
//    return new CacheUtil;
//};



