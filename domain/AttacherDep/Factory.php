<?php

namespace Voetbal\AttacherDep;

use Voetbal\ExternalSourceDep;
use Voetbal\Identifiable;
use Voetbal\AttacherDep\DepBase as AttacherBase;
use Voetbal\AttacherDep\Sport as SportAttacher;
use Voetbal\AttacherDep\Association as AssociationAttacher;
use Voetbal\AttacherDep\Season as SeasonAttacher;
use Voetbal\AttacherDep\League as LeagueAttacher;
use Voetbal\AttacherDep\Competition as CompetitionAttacher;
use Voetbal\AttacherDep\Competitor as CompetitorAttacher;
use Voetbal\Sport;
use Voetbal\Association;
use Voetbal\Season;
use Voetbal\League;
use Voetbal\Competition;
use Voetbal\Competitor;

class Factory
{
    public function createObject(Identifiable $importable, ExternalSourceDep $externalSource, $externalId): ?AttacherBase
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
