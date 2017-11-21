<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-3-17
 * Time: 16:21
 */

namespace Voetbal\PoulePlace;

use Voetbal\PoulePlace;
use Voetbal\Poule;

/**
 * Round
 *
 */
class Repository extends \Voetbal\Repository
{
    public function saveFromJSON( PoulePlace $place, Poule $poule )
    {
        // var_dump($poule->getId()); die();
        // $place->setPoule( $this->_em->merge( $poule ) );
        $place->setPoule( $poule );
        $this->_em->persist($place);
    }
}