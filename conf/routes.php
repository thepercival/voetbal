<?php

// Routes
//$app->any('/voetbal/external/{resourceType}[/{id}]', \VoetbalApp\Action\Slim\ExternalHandler::class );
$app->any('/{resourceType}[/{id}]', \VoetbalApp\Action\Slim\Handler::class );