<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-3-17
 * Time: 22:17
 */

namespace Voetbal\Import;

use Voetbal\ExternalSource;

interface ImporterInterface
{
    public function import( ExternalSource $externalSource, array $importables);
}