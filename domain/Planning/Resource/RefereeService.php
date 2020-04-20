<?php
/**
 * Created by PhpStorm->
 * User: coen
 * Date: 9-3-18
 * Time: 11:55
 */

namespace Voetbal\Planning\Resource;

use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Input;
use Voetbal\Planning\Batch;

class RefereeService
{
    /**
     * @var PlanningBase
     */
    private $planning;

    public function __construct(PlanningBase $planning)
    {
        $this->planning = $planning;
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    protected function refereesEnabled(): bool
    {
        return !$this->getInput()->getSelfReferee() && $this->getInput()->getNrOfReferees() > 0;
    }

    public function assign(Batch $batch)
    {
        if ($this->refereesEnabled() === false) {
            return false;
        }
        $this->assignBatch($batch->getLeaf(), $this->planning->getReferees()->toArray());
    }

    protected function assignBatch(Batch $batch, array $referees)
    {
        $games = array_reverse($batch->getGames());
        foreach ($games as $game) {
            $referee = array_shift($referees);
            $game->setReferee($referee);
            array_push($referees, $referee);
        }
        if ($batch->hasPrevious()) {
            $this->assignBatch($batch->getPrevious(), $referees);
        }
    }
}
