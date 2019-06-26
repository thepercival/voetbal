<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:18
 */

namespace Voetbal\Sport;

use Voetbal\Sport\Config\Score as ConfigScore;
use Voetbal\Ranking\Service as RankingService;
use Voetbal\Sport as SportBase;

class Config {

    /**
     * @var SportBase
     */
    protected $sport;
    /**
     * @var Competition
     */
    protected $competition;
    /**
     * @var int
     */
    protected $id;
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
     * @var int
     */
    protected $nrOfGameCompetitors;

    const DEFAULT_WINPOINTS = 3;
    const DEFAULT_DRAWPOINTS = 1;
    const POINTS_CALC_GAMEPOINTS = 0;
    const POINTS_CALC_SCOREPOINTS = 1;
    const POINTS_CALC_BOTH = 2;

    public function __construct( SportBase $sport, Competition $competition )
    {
        $this->sport = $sport;
        $this->competition = $competition;
        $this->competition->setSportConfig($this);
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

    public function getNrOfGameCompetitors(): ?int {
        return $this->nrOfGameCompetitors;
    }

    public function setNrOfGameCompetitors(int $nrOfGameCompetitors): void {
        $this->nrOfGameCompetitors = $nrOfGameCompetitors;
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
     * @return Competition
     */
    protected function getCompetition(): Competition
    {
        return $this->competition;
    }
}
