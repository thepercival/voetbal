<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:32
 */

use Voetbal\Competition;
include_once __DIR__ . '/../helpers/Serializer.php';

function createCompetition(): Competition
{
    $serializer = getSerializer();

    $json_raw = file_get_contents(__DIR__ . "/../data/competition.json");
    if($json_raw === false ) {
        throw new \Exception("competition-json not read well from file", E_ERROR);
    }
    $json = json_decode($json_raw, true);
    if($json === false ) {
        throw new \Exception("competition-json not read well from file", E_ERROR);
    }
    $jsonEncoded = json_encode($json);
    if($jsonEncoded === false ) {
        throw new \Exception("competition-json not read well from file", E_ERROR);
    }
    $competition = $serializer->deserialize($jsonEncoded, 'Voetbal\Competition', 'json');

    $sportSer = $serializer->deserialize( json_encode($json["sports"][0]), 'Voetbal\Sport', 'json');
    foreach( $competition->getSportConfigs() as $sportConfig ) {
        $refCl = new \ReflectionClass($sportConfig);
        $refClPropSport = $refCl->getProperty("sport");
        $refClPropSport->setAccessible(true);
        $refClPropSport->setValue($sportConfig, $sportSer );
        $refClPropSport->setAccessible(false);
    }

    foreach( $competition->getFields() as $field ) {
        $foundSports = $competition->getSports()->filter( function( $sport ) use ( $field ) {
            return $field->getSportIdSer() === $sport->getId();
        });
        $field->setSport( $foundSports->first() );
    }

    return $competition;
}