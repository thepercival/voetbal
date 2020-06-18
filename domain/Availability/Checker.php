<?php

namespace Voetbal\Availability;

use Exception;
use Voetbal\Competition;
use Voetbal\Field;
use Voetbal\Priority\Prioritizable;
use Voetbal\Referee;
use Voetbal\Sport\Config as SportConfig;

class Checker
{

    public function checkRefereeInitials(Competition $competition, string $initials, Referee $refereeToCheck = null)
    {
        $nonUniqueReferees = $competition->getReferees()->filter(
            function (Referee $refereeIt) use ($initials, $refereeToCheck): bool {
                return $refereeIt->getInitials() === $initials && $refereeToCheck !== $refereeIt;
            }
        );
        if (!$nonUniqueReferees->isEmpty()) {
            throw new Exception(
                "de scheidsrechter met de initialen " . $initials . " bestaat al",
                E_ERROR
            );
        }
    }

    public function checkRefereeEmailaddress(
        Competition $competition,
        string $emailaddress = null,
        Referee $refereeToCheck = null
    ) {
        if ($emailaddress === null) {
            return;
        }
        $nonUniqueReferees = $competition->getReferees()->filter(
            function (Referee $refereeIt) use ($emailaddress, $refereeToCheck): bool {
                return $refereeIt->getEmailaddress() === $emailaddress && $refereeToCheck !== $refereeIt;
            }
        );
        if (!$nonUniqueReferees->isEmpty()) {
            throw new Exception(
                "de scheidsrechter met het emailadres " . $emailaddress . " bestaat al",
                E_ERROR
            );
        }
    }

    /**
     * @param Competition $competition
     * @param int $priority
     * @param Referee|null $referee
     * @throws Exception
     */
    public function checkRefereePriority(Competition $competition, int $priority, Referee $referee = null)
    {
        return $this->checkPriority($competition->getReferees()->toArray(), $priority, $referee);
    }

    /**
     * @param SportConfig $sportConfig
     * @param int $priority
     * @param Field|null $field
     * @throws Exception
     */
    public function checkFieldPriority(SportConfig $sportConfig, int $priority, Field $field = null)
    {
        return $this->checkPriority($sportConfig->getFields()->toArray(), $priority, $field);
    }

    /**
     * @param array $prioritizables | Prioritizable[]
     * @param int $priority
     * @param Prioritizable|null $objectToCheck
     * @throws Exception
     */
    protected function checkPriority(array $prioritizables, int $priority, Prioritizable $objectToCheck = null)
    {
        $nonUniqueObjects = array_filter(
            $prioritizables,
            function (Prioritizable $prioritizableIt) use ($priority, $objectToCheck): bool {
                return $prioritizableIt->getPriority() === $priority && $objectToCheck !== $prioritizableIt;
            }
        );
        if (count($nonUniqueObjects) > 0) {
            throw new Exception(
                "de prioriteit " . $priority . " bestaat al",
                E_ERROR
            );
        }
    }
}