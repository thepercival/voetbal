<?php

// Routes
//$app->any('/voetbal/external/{resourceType}[/{id}]', \Voetbal\App\Action\Slim\ExternalHandler::class );
$app->any('/{resourceType}[/{id}]', \Voetbal\Appx\Action\Slim\Handler::class );