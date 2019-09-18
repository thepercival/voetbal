<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:18
 */

namespace Voetbal\Sport;

use Voetbal\Game;
use Voetbal\Sport as SportBase;
use Voetbal\Sport\IdSer as SportIdSer;
use Voetbal\Competition;

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
    protected $nrOfGamePlaces;

    use SportIdSer;

    const DEFAULT_WINPOINTS = 3;
    const DEFAULT_DRAWPOINTS = 1;
    const POINTS_CALC_GAMEPOINTS = 0;
    const POINTS_CALC_SCOREPOINTS = 1;
    const POINTS_CALC_BOTH = 2;
    const DEFAULT_NROFGAMEPLACES = 2;

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
     * @param double $winPointsExt
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

    public function getPointsCustom(int $result, int $phase): float {
        if ($result === Game::RESULT_DRAW) {
            if ($phase === Game::PHASE_REGULARTIME) {
                return $this->getDrawPoints();
            } else if ($phase === Game::PHASE_EXTRATIME) {
                return $this->getDrawPointsExt();
            }
            return 0;
        }
        if ($phase === Game::PHASE_REGULARTIME) {
            return $this->getWinPoints();
        } else if ($phase === Game::PHASE_EXTRATIME) {
            return $this->getWinPointsExt();
        }
        return 0;
    }

    /**
     * @param double $drawPointsExt
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

    public function getNrOfGamePlaces(): ?int {
        return $this->nrOfGamePlaces;
    }

    public function setNrOfGamePlaces(int $nrOfGamePlaces): void {
        $this->nrOfGamePlaces = $nrOfGamePlaces;
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
    public function getCompetition(): Competition
    {
        return $this->competition;
    }
}
