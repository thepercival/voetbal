<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:18
 */

namespace Voetbal\Config;

use Voetbal\Config\Count\Supplier as CountConfigSupplier;
use Voetbal\Config\Score as ConfigScore;
use Voetbal\Sport;

class Count {

    /**
     * @var Sport
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
     * @var Score
     */
    protected $score;
    /**
     * @var ArrayCollection
     */
    protected $scoresDep;
    /**
     * @var int
     */
    protected $pointsCalculation;

    const DEFAULT_WINPOINTS = 3;
    const DEFAULT_DRAWPOINTS = 1;
    const POINTS_CALC_GAMEPOINTS = 0;
    const POINTS_CALC_SCOREPOINTS = 1;
    const POINTS_CALC_BOTH = 2;






    public function __construct( Sport $sport, Count\Supplier $supplier )
    {
        $this->setSport($sport);
        $this->supplier = $supplier;
        $this->supplier->setCountConfig($this);

        // this.winPoints = this.sport.getDefaultWinPoints();
        // this.drawPoints = this.sport.getDefaultDrawPoints();
        // this.winPointsExt = this.sport.getDefaultWinPointsExt();
        // this.drawPointsExt = this.sport.getDefaultDrawPointsExt;
        // this.setScore(this.createScoreConfig(config));
        $this->pointsCalculation = Count::POINTS_CALC_GAMEPOINTS;

        $this->scoresDep = new ArrayCollection();
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
     * @return Score
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param Score $score
     */
    public function setScore( Score $score)
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
     * @return Sport
     */
    public function getSport(): Sport
    {
        return $this->sport;
    }

    /**
     * @return Count\Supplier
     */
    protected function getSupplier(): Count\Supplier
    {
        return $this->supplier;
    }
}
