<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 12-6-2019
 * Time: 11:10
 */

namespace Voetbal\TestHelper;

use Voetbal\Range as VoetbalRange;
use Voetbal\Structure\Options as StructureOptions;

trait DefaultStructureOptions {
    protected function getDefaultStructureOptions(): StructureOptions
    {
        return $options = new StructureOptions(
            new VoetbalRange(1, 64), // pouleRange
            new VoetbalRange(1, 128), // placeRange
            new VoetbalRange(1, 40) // placesPerPouleRange
        );
    }
}


