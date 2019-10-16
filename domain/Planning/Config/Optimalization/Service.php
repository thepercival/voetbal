<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 20-6-2019
 * Time: 12:23
 */

namespace Voetbal\Planning\Config\Optimalization;

use Voetbal\Planning\Config\Optimalization as ConfigOptimalization;
use Voetbal\Sport;

class Service
{
    /**
     * @var array
     */
    protected $optimalizations = [];

    public function getOptimalization( int $nrOfFields, bool $selfReferee, int $nrOfReferees, int $nrOfPoules, int $nrOfPlaces, bool $teamup ): ConfigOptimalization {
        $id = $this->getId( $nrOfFields, $selfReferee, $nrOfReferees, $nrOfPoules, $nrOfPlaces, $teamup );
        if( array_key_exists( $id, $this->optimalizations ) ) {
            return $this->optimalizations[$id];
        }
        $optimalization = new ConfigOptimalization( $nrOfFields, $selfReferee, $nrOfReferees, $nrOfPoules, $nrOfPlaces, $teamup );
        $this->optimalizations[$id] = $optimalization;
        return $optimalization;
    }

    protected function getId( int $nrOfFields, bool $selfReferee, int $nrOfReferees, int $nrOfPoules, int $nrOfPlaces, bool $teamup ): string {
        return $nrOfFields . "-" . ( $selfReferee ? '1' : '0') . "-" . $nrOfReferees . "-" . $nrOfPoules . "-" . $nrOfPlaces . "-" .  ( $teamup ? '1' : '0');
    }
}
