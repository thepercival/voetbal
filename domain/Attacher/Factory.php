<?php

namespace Voetbal\Attacher;

use Voetbal\ExternalSource;
use Voetbal\Import\Idable as Importable;
use Voetbal\Attacher as AttacherBase;
use Voetbal\Attacher\Sport as SportAttacher;
use Voetbal\Attacher\Association as AssociationAttacher;
use Voetbal\Attacher\Season as SeasonAttacher;
use Voetbal\Attacher\League as LeagueAttacher;
use Voetbal\Attacher\Competition as CompetitionAttacher;
use Voetbal\Attacher\Competitor as CompetitorAttacher;
use Voetbal\Sport;
use Voetbal\Association;
use Voetbal\Season;
use Voetbal\League;
use Voetbal\Competition;
use Voetbal\Competitor;

class Factory
{
    public function createObject(Importable $importable, ExternalSource $externalSource, $externalId): ?AttacherBase
    {
        if ($importable instanceof Sport) {
            return new SportAttacher(
                $importable,
                $externalSource,
                $externalId
            );
        } elseif ($importable instanceof Association) {
            return new AssociationAttacher(
                $importable,
                $externalSource,
                $externalId
            );
        } elseif ($importable instanceof Season) {
            return new SeasonAttacher(
                $importable,
                $externalSource,
                $externalId
            );
        } elseif ($importable instanceof League) {
            return new LeagueAttacher(
                $importable,
                $externalSource,
                $externalId
            );
        } elseif ($importable instanceof Competition) {
            return new CompetitionAttacher(
                $importable,
                $externalSource,
                $externalId
            );
        } elseif ($importable instanceof Competitor) {
            return new CompetitorAttacher(
                $importable,
                $externalSource,
                $externalId
            );
        }
        return null;
    }
}
