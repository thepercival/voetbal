<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:18
 */

namespace Voetbal\Sport;

use Voetbal\Sport\CountConfig\Supplier as CountConfigSupplier;
use Voetbal\Sport\CountConfig\Score as ConfigScore;
use Voetbal\Ranking\Service as RankingService;
use Voetbal\Sport as SportBase;

class CountConfig {

    /**
     * @var SportBase
     */
    protected $sport;
    /**
     * @var CountConfigSupplier
     */
    protected $supplier;
    /**
     * @var int
     */
    protected $id;
    /**
     * @var int
     */
    protected $qualifyRule;
    /**
     * @var double
     */
    protected $winPoints;
    /**
     * @var double
     */
    protected $drawPoints;
    /**
     * @var double
     */
    protected $winPointsExt;
    /**
     * @var double
     */
    protected $drawPointsExt;
    /**
     * @var int
     */
    protected $pointsCalculation;
    /**
     * @var ConfigScore
     */
    protected $score;

    const DEFAULT_WINPOINTS = 3;
    const DEFAULT_DRAWPOINTS = 1;
    const POINTS_CALC_GAMEPOINTS = 0;
    const POINTS_CALC_SCOREPOINTS = 1;
    const POINTS_CALC_BOTH = 2;

    public function __construct( SportBase $sport, CountConfigSupplier $supplier )
    {
        $this->sport = $sport;
        $this->supplier = $supplier;
        $this->supplier->setCountConfig($this);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id = null)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getQualifyRule()
    {
        return $this->qualifyRule;
    }

    /**
     * @param int $qualifyRule
     */
    public function setQualifyRule( int $qualifyRule)
    {
        if ( $qualifyRule < RankingService::RULESSET_WC or $qualifyRule > RankingService::RULESSET_EC) {
            throw new \InvalidArgumentException("de kwalificatieregel heeft een onjuiste waarde", E_ERROR);
        }
        $this->qualifyRule = $qualifyRule;
    }

    /**
     * @return double
     */
    public function getWinPoints()
    {
        return $this->winPoints;
    }

    /**
     * @param double $winPoints
     */
    public function setWinPoints($winPoints)
    {
        $this->winPoints = $winPoints;
    }

    /**
     * @return double
     */
    public function getDrawPoints()
    {
        return $this->drawPoints;
    }

    /**
     * @param double $drawPoints
     */
    public function setDrawPoints($drawPoints)
    {
        $this->drawPoints = $drawPoints;
    }

    /**
     * @return double
     */
    public function getWinPointsExt()
    {
        return $this->winPointsExt;
    }

    /**
     * @param int $winPointsExt
     */
    public function setWinPointsExt($winPointsExt)
    {
        $this->winPointsExt = $winPointsExt;
    }

    /**
     * @return double
     */
    public function getDrawPointsExt()
    {
        return $this->drawPointsExt;
    }

    /**
     * @param int $drawPointsExt
     */
    public function setDrawPointsExt($drawPointsExt)
    {
        $this->drawPointsExt = $drawPointsExt;
    }

    /**
     * @return ConfigScore
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @return int
     */
    public function getPointsCalculation(): int {
        return $this->pointsCalculation;
    }

    /**
     * @param int $pointsCalculation
     */
    public function setPointsCalculation(int $pointsCalculation) {
        $this->pointsCalculation = $pointsCalculation;
    }

    /**
     * @param ConfigScore $score
     */
    public function setScore( ConfigScore $score)
    {
        $this->score = $score;
    }

    /**
     * @return ConfigScore
     */
    public function getInputScore(): ConfigScore
    {
        $parentScoreConfig = $this->getScore();
        $childScoreConfig = $parentScoreConfig->getChild();
        while ($childScoreConfig !== null && ( $childScoreConfig->getMaximum() > 0 || $parentScoreConfig->getMaximum() === 0 )) {
            $parentScoreConfig = $childScoreConfig;
            $childScoreConfig = $childScoreConfig->getChild();
        }
        return $parentScoreConfig;
    }

    /**
     * @return ConfigScore
     */
    public function getCalculateScore(): ConfigScore
    {
        $score = $this->getScore();
        while ($score->getMaximum() === 0 && $score->getChild() !== null) {
            $score = $score->getChild();
        }
        return $score;
    }

    /**
     * @return SportBase
     */
    public function getSport(): SportBase
    {
        return $this->sport;
    }
    /**
     * @return int
     */
    public function getSportId(): int
    {
        return $this->sport->getId();
    }


    /**
     * @return CountConfigSupplier
     */
    protected function getSupplier(): CountConfigSupplier
    {
        return $this->supplier;
    }
}