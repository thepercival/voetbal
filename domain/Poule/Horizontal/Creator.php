<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 19:18
 */

namespace Voetbal\Poule\Horizontal;

use Voetbal\Qualify\Group as QualifyGroup;

class Creator
{
    /**
     * @var QualifyGroup
     */
    public $qualifyGroup;
    /**
     * @var int
     */
    public $nrOfQualifiers;

    public function __construct(QualifyGroup $qualifyGroup, int $nrOfQualifiers)
    {
        $this->qualifyGroup = $qualifyGroup;
        $this->nrOfQualifiers = $nrOfQualifiers;
    }
}
