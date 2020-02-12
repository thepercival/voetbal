<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning\Input;

use Voetbal\Planning\Input as PlanningInput;
use Voetbal\Planning\Resources;
use Voetbal\Range as VoetbalRange;
use Voetbal\Planning\Config\Service as PlanningConfigService;
use Voetbal\Sport;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Structure\Options as StructureOptions;

class Iterator
{
    /**
     * @var StructureOptions
     */
    protected $structureRanges;
    /**
     * @var VoetbalRange
     */
    protected $rangeNrOfSports;
    /**
     * @var VoetbalRange
     */
    protected $rangeNrOfReferees;
    /**
     * @var VoetbalRange
     */
    protected $rangeNrOfFields;
    /**
     * @var VoetbalRange
     */
    protected $rangeNrOfHeadtohead;
    /**
     * @var int
     */
    protected $maxFieldsMultipleSports = 6;
    /**
     * @var StructureService
     */
    protected $structureService;
    /**
     * @var PlanningConfigService
     */
    protected $planningConfigService;
    /**
     * @var int
     */
    protected $nrOfPlaces;
    /**
     * @var int
     */
    protected $nrOfPoules;
    /**
     * @var int
     */
    protected $nrOfSports;
    /**
     * @var int
     */
    protected $nrOfReferees;
    /**
     * @var int
     */
    protected $nrOfFields;
    /**
     * @var int
     */
    protected $nrOfHeadtohead;
    /**
     * @var bool
     */
    protected $teamup;
    /**
     * @var bool
     */
    protected $selfReferee;

    /**
     * @var bool
     */
    protected $incremented;

    public function __construct(
        StructureOptions $options,
        VoetbalRange $rangeNrOfSports,
        VoetbalRange $rangeNrOfFields,
        VoetbalRange $rangeNrOfReferees,
        VoetbalRange $rangeNrOfHeadtohead
    ) {
        $this->structureRanges = $options;
        $this->rangeNrOfSports = $rangeNrOfSports;
        $this->rangeNrOfFields = $rangeNrOfFields;
        $this->rangeNrOfReferees = $rangeNrOfReferees;
        $this->rangeNrOfHeadtohead = $rangeNrOfHeadtohead;
        $this->maxFieldsMultipleSports = 6;

        $this->structureService = new StructureService($options);
        $this->planningConfigService = new PlanningConfigService();

        $this->incremented = false;
        $this->init();
    }

    protected function getSportConfig(int $nrOfSports, int $nrOfFields): array
    {
        $sports = [];
        $nrOfFieldsPerSport = (int)ceil($nrOfFields / $nrOfSports);
        for ($sportNr = 1; $sportNr <= $nrOfSports; $sportNr++) {
            $sports[] = ["nrOfFields" => $nrOfFieldsPerSport, "nrOfGamePlaces" => Sport::TEMPDEFAULT];
            $nrOfFields -= $nrOfFieldsPerSport;
            if (($nrOfFieldsPerSport * ($nrOfSports - $sportNr)) > $nrOfFields) {
                $nrOfFieldsPerSport--;
            }
        }
        return $sports;
    }

    protected function init()
    {
        $this->initNrOfPlaces();
    }

    protected function initNrOfPlaces()
    {
        $this->nrOfPlaces = $this->structureRanges->getPlaceRange()->max;
        $this->initNrOfPoules();
    }

    protected function initNrOfPoules()
    {
        $this->nrOfPoules = $this->structureRanges->getPouleRange()->min;
        $nrOfPlacesPerPoule = $this->structureService->getNrOfPlacesPerPoule(
            $this->nrOfPlaces,
            $this->nrOfPoules,
            true
        );
        while ($nrOfPlacesPerPoule > $this->structureRanges->getPlacesPerPouleRange()->max) {
            $this->nrOfPoules++;
            $nrOfPlacesPerPoule = $this->structureService->getNrOfPlacesPerPoule(
                $this->nrOfPlaces,
                $this->nrOfPoules,
                true
            );
        }
        $this->initNrOfSports();
    }

    protected function initNrOfSports()
    {
        $this->nrOfSports = $this->rangeNrOfSports->min;
        $this->initNrOfFields();
    }

    protected function initNrOfFields()
    {
        if ($this->rangeNrOfFields->min >= $this->nrOfSports) {
            $this->nrOfFields = $this->rangeNrOfFields->min;
        } else {
            $this->nrOfFields = $this->nrOfSports;
        }
        $this->initNrOfReferees();
    }

    protected function initNrOfReferees()
    {
        $this->nrOfReferees = $this->rangeNrOfReferees->min;
        $this->initNrOfHeadtohead();
    }

    protected function initNrOfHeadtohead()
    {
        $this->nrOfHeadtohead = $this->rangeNrOfHeadtohead->min;
        $this->initTeamup();
    }

    protected function initTeamup()
    {
        $this->teamup = false;
        $this->initSelfReferee();
    }

    protected function initSelfReferee()
    {
        $this->selfReferee = false;
    }


    //     return [json_decode(json_encode(["selfReferee" => $selfReferee, "teamup" => $teamup]))];
    public function increment(): ?PlanningInput
    {
        if( $this->incremented === false ) {
            $this->incremented = true;
            return $this->createInput();
        }

        if ($this->incrementValue() === false) {
            return null;
        }

        $planningInput = $this->createInput();

        $maxNrOfRefereesInPlanning = $planningInput->getMaxNrOfBatchGames(
            Resources::FIELDS + Resources::PLACES
        );
        if ($this->nrOfReferees < $this->nrOfFields && $this->nrOfReferees > $maxNrOfRefereesInPlanning) {
            if ($this->incrementNrOfFields() === false) {
                return null;
            }
            return $this->createInput();
        }

        $maxNrOfFieldsInPlanning = $planningInput->getMaxNrOfBatchGames(
            Resources::REFEREES + Resources::PLACES
        );
        if ($this->nrOfFields < $this->nrOfReferees && $this->nrOfFields > $maxNrOfFieldsInPlanning) {
            if ($this->incrementNrOfSports() === false) {
                return null;
            }
            return $this->createInput();
        }

        return $planningInput;
    }

    protected function createInput(): PlanningInput
    {
        $structureConfig = $this->structureService->getStructureConfig($this->nrOfPlaces, $this->nrOfPoules);
        $sportConfig = $this->getSportConfig($this->nrOfSports, $this->nrOfFields);
        return new PlanningInput(
            $structureConfig,
            $sportConfig,
            $this->nrOfReferees,
            $this->teamup,
            $this->selfReferee,
            $this->nrOfHeadtohead
        );
    }

    public function incrementValue(): bool
    {
        return $this->incrementSelfReferee();
    }

    protected function incrementSelfReferee(): bool
    {
        if ($this->nrOfReferees > 0 || $this->selfReferee === true) {
            return $this->incrementTeamup();
        }
        $selfRefereeIsAvailable = $this->planningConfigService->canSelfRefereeBeAvailable(
            $this->teamup,
            $this->nrOfPlaces
        );
        if ($selfRefereeIsAvailable === false ) {
            return $this->incrementTeamup();
        }
        $this->selfReferee = true;
        return true;
    }

    protected function incrementTeamup(): bool
    {
        if ($this->teamup === true) {
            return $this->incrementNrOfHeadtohead();
        }
        $structureConfig = $this->structureService->getStructureConfig($this->nrOfPlaces, $this->nrOfPoules);
        $sportConfig = $this->getSportConfig($this->nrOfSports, $this->nrOfFields);
        $teamupAvailable = $this->planningConfigService->canTeamupBeAvailable($structureConfig, $sportConfig);
        if ($teamupAvailable === false ) {
            return $this->incrementNrOfHeadtohead();
        }
        $this->teamup = true;
        $this->initSelfReferee();
        return true;
    }

    protected function incrementNrOfHeadtohead(): bool
    {
        if ($this->nrOfHeadtohead === $this->rangeNrOfHeadtohead->max) {
            return $this->incrementNrOfReferees();;
        }
        $this->nrOfHeadtohead++;
        $this->initTeamup();
        return true;
    }

    protected function incrementNrOfReferees(): bool
    {
        $maxNrOfReferees = $this->rangeNrOfReferees->max;
        $maxNrOfRefereesByPlaces = (int)( ceil( $this->nrOfPlaces / 2 ) );
        if ($this->nrOfReferees >= $maxNrOfReferees || $this->nrOfReferees >= $maxNrOfRefereesByPlaces ) {
            return $this->incrementNrOfFields();;
        }
        $this->nrOfReferees++;
        $this->initNrOfHeadtohead();
        return true;
    }

    protected function incrementNrOfFields(): bool
    {
        $maxNrOfFields = $this->rangeNrOfFields->max;
        $maxNrOfFieldsByPlaces = (int)( ceil( $this->nrOfPlaces / 2 ) );
        if ($this->nrOfFields >= $maxNrOfFields || $this->nrOfFields >= $maxNrOfFieldsByPlaces ) {
            return $this->incrementNrOfSports();;
        }
        $this->nrOfFields++;
        $this->initNrOfReferees();
        return true;
    }

    protected function incrementNrOfSports(): bool
    {
        if ($this->nrOfSports === $this->rangeNrOfSports->max) {
            return $this->incrementNrOfPoules();;
        }
        $this->nrOfSports++;
        $this->initNrOfFields();
        return true;
    }

    protected function incrementNrOfPoules(): bool
    {
        if ($this->nrOfPoules === $this->structureRanges->getPouleRange()->max) {
            return $this->incrementNrOfPlaces();
        }
        $nrOfPlacesPerPoule = $this->structureService->getNrOfPlacesPerPoule(
            $this->nrOfPlaces,
            $this->nrOfPoules + 1,
            true
        );
        if ($nrOfPlacesPerPoule < $this->structureRanges->getPlacesPerPouleRange()->min) {
            return $this->incrementNrOfPlaces();
        }

        $this->nrOfPoules++;
        $this->initNrOfSports();
        return true;
    }

    protected function incrementNrOfPlaces(): bool
    {
        if ($this->nrOfPlaces === $this->structureRanges->getPlaceRange()->min) {
            return false;
        }
        $this->nrOfPlaces--;
        $this->initNrOfPoules();
        return true;
    }





    /*if ($nrOfCompetitors === 6 && $nrOfPoules === 1 && $nrOfSports === 1 && $nrOfFields === 2
        && $nrOfReferees === 0 && $nrOfHeadtohead === 1 && $teamup === false && $selfReferee === false ) {
        $w1 = 1;
    } else*/ /*if ($nrOfCompetitors === 12 && $nrOfPoules === 2 && $nrOfSports === 1 && $nrOfFields === 4
            && $nrOfReferees === 0 && $nrOfHeadtohead === 1 && $teamup === false && $selfReferee === false ) {
            $w1 = 1;
        } else {
            continue;
        }*/

//        $multipleSports = count($sportConfig) > 1;
//        $newNrOfHeadtohead = $nrOfHeadtohead;
//        if ($multipleSports) {
//            //                                    if( count($sportConfig) === 4 && $sportConfig[0]["nrOfFields"] == 1 && $sportConfig[1]["nrOfFields"] == 1
//            //                                        && $sportConfig[2]["nrOfFields"] == 1 && $sportConfig[3]["nrOfFields"] == 1
//            //                                        && $teamup === false && $selfReferee === false && $nrOfHeadtohead === 1 && $structureConfig == [3]  ) {
//            //                                        $e = 2;
//            //                                    }
//            $newNrOfHeadtohead = $this->planningInputSerivce->getSufficientNrOfHeadtohead(
//                $nrOfHeadtohead,
//                min($structureConfig),
//                $teamup,
//                $selfReferee,
//                $sportConfig
//            );
//        }

//        $planningInput = new PlanningInput(
//            $structureConfig,
//            $sportConfig,
//            $nrOfReferees,
//            $teamup,
//            $selfReferee,
//            $newNrOfHeadtohead
//        );
//
//        if (!$multipleSports) {
//            $maxNrOfFieldsInPlanning = $planningInput->getMaxNrOfBatchGames(
//                Resources::REFEREES + Resources::PLACES
//            );
//            if ($nrOfFields > $maxNrOfFieldsInPlanning) {
//                return;
//            }
//        } else {
//            if ($nrOfFields > self::MAXNROFFIELDS_FOR_MULTIPLESPORTS) {
//                return;
//            }
//        }
}
