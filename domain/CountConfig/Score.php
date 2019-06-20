<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 9:53
 */

namespace Voetbal\CountConfig;

use Voetbal\CountConfig as CountConfigBase;

class Score
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var CountConfigBase
     */
    protected $countConfig;
    /**
     * @var Score
     */
    protected $parent;
    /**
     * @var Score
     */
    protected $child;
    /**
     * @var int
     */
    protected $direction;
    /**
     * @var int
     */
    protected $maximum;
    /**
     * @var CountConfigBase
     */
    protected $config;

    protected $roundconfigiddep; // DEPRECATED
    protected $iddep;  // DEPRECATED

    const UPWARDS = 1;
    const DOWNWARDS = 2;

    public function __construct( CountConfigBase $config, Score $parent = null )
    {
        $this->setConfig( $config );
        $this->setParent( $parent );
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId( int $id )
    {
        $this->id = $id;
    }

    /**
     * @return Score
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Score $scoreConfig
     */
    public function setParent( Score $scoreConfig = null )
    {
        $this->parent = $scoreConfig;
        if( $this->parent !== null ) {
            $this->parent->setChild( $this );
        }
    }

    /**
     * @return Score
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @param Score $scoreConfig
     */
    public function setChild( Score $scoreConfig = null )
    {
        $this->child = $scoreConfig;
    }

    /**
     * @return Score
     */
    public function getRoot()
    {
        $parent = $this->getParent();
        if( $parent !== null ) {
            return $parent->getRoot();
        }
        return $this;
    }

    /**
     * @return CountConfigBase
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param CountConfigBase $config
     */
    protected function setConfig( CountConfigBase $config )
    {
        $this->config = $config;
        $this->config->setScore($this);
    }

    /**
     * @return int
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * @param int $direction
     */
    public function setDirection( $direction )
    {
        if ( $direction !== Score::UPWARDS and $direction !== Score::DOWNWARDS ) {
            throw new \InvalidArgumentException( "de richting heeft een onjuiste waarde", E_ERROR );
        }
        $this->direction = $direction;
    }

    /**
     * @return int
     */
    public function getMaximum()
    {
        return $this->maximum;
    }

    /**
     * @param int $maximum
     */
    public function setMaximum( $maximum )
    {
        if ( $maximum !== null and !is_int( $maximum ) ){
            throw new \InvalidArgumentException( "het maximum heeft een onjuiste waarde", E_ERROR );
        }
        $this->maximum = $maximum;
    }
}