<?php

// Routes
//$app->any('/voetbal/external/{resourceType}[/{id}]', \Voetbal\App\Action\Slim\ExternalHandler::class );
$app->any('/{resourceType}[/{id}]', \Voetbal\App\Action\Slim\Handler::class );