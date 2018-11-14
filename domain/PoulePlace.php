<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-17
 * Time: 16:04
 */

namespace Voetbal;

use Voetbal\Qualify\Rule as QualifyRule;

class PoulePlace
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $number;

    /**
     * @var int
     */
    protected $penaltyPoints;

    /**
     * @var Poule
     */
    protected $poule;

    /**
     * @var Team
     */
    protected $team;

    /**
     * @var QualfyRule
     */
    protected $fromQualifyRule;

    /**
     * @var QualfyRule[] | array
     */
    protected $toQualifyRules = array();

    const MIN_LENGTH_NAME = 10;

    public function __construct( Poule $poule, $number )
    {
        $this->setPoule( $poule );
        $this->setNumber( $number );
        $this->setPenaltyPoints( 0 );
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     */
    public function setId( $id )
    {
        $this->id = $id;
    }

    /**
     * @return Poule
     */
    public function getPoule(): Poule
    {
        return $this->poule;
    }

    /**
     * @param Poule $poule
     */
    public function setPoule( Poule $poule )
    {
        if ( $this->poule !== null && $this->poule->getPlaces()->contains( $this ) ){
            $this->poule->getPlaces()->removeElement($this) ;
        }
        if ( $poule !== null && !$poule->getPlaces()->contains( $this ) ){
            $poule->getPlaces()->add($this) ;
        }
        $this->poule = $poule;
    }

    /**
     * @return Round
     */
    public function getRound(): Round
    {
        return $this->getPoule()->getRound();
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber( $number )
    {
        if ( !is_int( $number )   ){
            throw new \InvalidArgumentException( "het rondenumber heeft een onjuiste waarde", E_ERROR );
        }
        $this->number = $number;
    }

    /**
     * @return int
     */
    public function getPenaltyPoints()
    {
        return $this->penaltyPoints;
    }

    /**
     * @param int $penaltyPoints
     */
    public function setPenaltyPoints( int $penaltyPoints )
    {
        $this->penaltyPoints = $penaltyPoints;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string
     */
    public function setName( $name )
    {
        if ( is_string($name) and strlen( $name ) === 0 )
            $name = null;

        if ( strlen( $name ) > static::MAX_LENGTH_NAME ){
            throw new \InvalidArgumentException( "de naam mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }

        if(preg_match('/[^a-z0-9 ]/i', $name)){
            throw new \InvalidArgumentException( "de naam mag alleen cijfers, letters en spaties bevatten", E_ERROR );
        }

        $this->name = $name;
    }

    /**
     * @return Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param Team $team
     */
    public function setTeam( Team $team = null )
    {
        $this->team = $team;
    }

    public function getFromQualifyRule(): ?QualifyRule
    {
        return $this->fromQualifyRule;
    }

    public function setFromQualifyRule(QualifyRule $qualifyRule )
    {
        $this->fromQualifyRule = $qualifyRule;
    }

    public function getToQualifyRules(): array /*QualifyRule*/
    {
        return $this->toQualifyRules;
    }

    public function getToQualifyRule(int $winnersOrLosers)
    {
        $filtered = array_filter( $this->toQualifyRules, function ($qualifyRule) use ($winnersOrLosers) {
            return ($qualifyRule->getWinnersOrLosers() === $winnersOrLosers);
        });
        $toQualifyRule = reset( $filtered );
        return $toQualifyRule !== false ? $toQualifyRule : null;
    }

    public function setToQualifyRule(int $winnersOrLosers, QualifyRule $qualifyRule = null )
    {
        $toQualifyRuleOld = $this->getToQualifyRule($winnersOrLosers);
        if ($toQualifyRuleOld !== null) {
            // toQualifyRuleOld.removeFromPoulePlace( this );
            if (($key = array_search($toQualifyRuleOld, $this->toQualifyRules)) !== false) {
                unset($this->toQualifyRules[$key]);
            }
        }
        if ($qualifyRule) {
            $this->toQualifyRules[] = $qualifyRule;
        }
    }
}