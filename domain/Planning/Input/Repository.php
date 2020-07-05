<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace Voetbal\Planning\Input;

use Voetbal\Planning;
use Voetbal\Planning\Validator as PlanningValidator;
use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Input as PlanningInput;

/**
 * Game
 */
class Repository extends \Voetbal\Repository
{
    public function get(
        array $structureConfig,
        array $sportConfig,
        int $nrOfReferees,
        bool $teamup,
        bool $selfReferee,
        int $nrOfHeadtohead
    ): ?PlanningInput {
        $query = $this->createQueryBuilder('pi')
            ->where('pi.structureConfig = :structureConfig')
            ->andWhere('pi.sportConfig = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
        ;

        $query = $query->setParameter('structureConfig', json_encode($structureConfig));
        $query = $query->setParameter('sportConfig', json_encode($sportConfig));
        $query = $query->setParameter('nrOfReferees', $nrOfReferees);
        $query = $query->setParameter('teamup', $teamup);
        $query = $query->setParameter('selfReferee', $selfReferee);
        $query = $query->setParameter('nrOfHeadtohead', $nrOfHeadtohead);

        $query->setMaxResults(1);

        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }

    public function getFromInput(PlanningInput $input): ?PlanningInput
    {
        return $this->get(
            $input->getStructureConfig(),
            $input->getSportConfig(),
            $input->getNrOfReferees(),
            $input->getTeamup(),
            $input->getSelfReferee(),
            $input->getNrOfHeadtohead()
        );
    }

    public function reset(PlanningInput $planningInput)
    {
        while ($planningInput->getPlannings()->count() > 0) {
            $planning = $planningInput->getPlannings()->first();
            $planningInput->getPlannings()->removeElement($planning);
            $this->remove($planning);
        }
        $planningInput->setState(PlanningInput::STATE_CREATED);
        $this->save($planningInput);
    }

    //-- planninginputs not validated
//select 	count(*)
//from 	planninginputs pi
//where 	not exists( select * from plannings p where p.inputId = pi.Id and ( p.state = 2 or p.state = 8 or p.state = 16 ) )
//and		exists( select * from plannings p where p.inputId = pi.Id and p.validity < 0 )
    /**
     * @param int $limit
     * @return array|PlanningInput[]
     */
    public function findNotValidated(int $limit): array
    {
        $exprNot = $this->getEM()->getExpressionBuilder();
        $exprInvalidStates = $this->getEM()->getExpressionBuilder();
        $exprNotValidated = $this->getEM()->getExpressionBuilder();

        $states = PlanningBase::STATE_TIMEOUT + PlanningBase::STATE_UPDATING_SELFREFEE + PlanningBase::STATE_PROCESSING;

        $query = $this->createQueryBuilder('pi')
            ->where('pi.state = :inputState')
            ->andWhere(
                $exprNot->not(
                    $exprInvalidStates->exists(
                        $this->getEM()->createQueryBuilder()
                            ->select('p1.id')
                            ->from('Voetbal\Planning', 'p1')
                            ->where('p1.input = pi')
                            ->andWhere('BIT_AND(p1.state, :states) > 0')
                            ->getDQL()
                    )
                )
            )
            ->andWhere(
                $exprNotValidated->exists(
                    $this->getEM()->createQueryBuilder()
                        ->select('p2.id')
                        ->from('Voetbal\Planning', 'p2')
                        ->where('p2.input = pi')
                        ->andWhere('p2.validity = :notvalidated')
                        ->getDQL()
                )
            )
            ->setMaxResults($limit)
            ->setParameter('inputState', PlanningInput::STATE_ALL_PLANNINGS_TRIED)
            ->setParameter('states', $states)
            ->setParameter('notvalidated', PlanningValidator::NOT_VALIDATED);
        $inputs = $query->getQuery()->getResult();

        return $inputs;
    }


//    -- obsolete planninginputs
//    select 	count(*)
//    from 	planninginputs pi
//    where 	not exists( select * from plannings p where p.inputId = pi.Id and ( p.state = 2 or p.state = 8 or p.state = 16 ) )
//    and		( select count(*) from plannings p where p.inputId = pi.Id and p.state = 4 ) > 1 --success
//    and		pi.state = 8
    /**
     * @return array|PlanningInput[]
     */
    public function findWithObsoletePlannings(): array
    {
        $exprNot = $this->getEM()->getExpressionBuilder();
        $exprInvalidStates = $this->getEM()->getExpressionBuilder();
        $exprNotValidated = $this->getEM()->getExpressionBuilder();

        $states = PlanningBase::STATE_TIMEOUT + PlanningBase::STATE_UPDATING_SELFREFEE + PlanningBase::STATE_PROCESSING;

        $query = $this->createQueryBuilder('pi')
            ->where('pi.state = :inputState')
            ->andWhere(
                $exprNot->not(
                    $exprInvalidStates->exists(
                        $this->getEM()->createQueryBuilder()
                            ->select('p1.id')
                            ->from('Voetbal\Planning', 'p1')
                            ->where('p1.input = pi')
                            ->andWhere('BIT_AND(p1.state, :states) > 0')
                            ->getDQL()
                    )
                )
            )
            ->andWhere(
                "(" . $this->getEM()->createQueryBuilder()
                    ->select('count(p2.id)')
                    ->from('Voetbal\Planning', 'p2')
                    ->where('p2.input = pi')
                    ->andWhere('p2.state = :planningState')
                    ->getDQL()
                . ") > 1"
            )
            ->setParameter('inputState', PlanningInput::STATE_ALL_PLANNINGS_TRIED)
            ->setParameter('states', $states)
            ->setParameter('planningState', Planning::STATE_SUCCESS);
        $inputs = $query->getQuery()->getResult();

        return $inputs;
    }
}
