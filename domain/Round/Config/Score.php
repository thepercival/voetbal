<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 9:53
 */

namespace Voetbal\Round\Config;

use Voetbal\Round;
use Voetbal\Round\Config;
use Voetbal\Round\Config\Score\OptionsTrait;

class Score
{
    use OptionsTrait;

    /**
     * @var int
     */
    protected $id;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Score
     */
    protected $parent;
    /**
     * @var Score
     */
    protected $child;

    public function __construct( Config $config, $name, $direction, $maximum, Score $parent = null )
    {
        $this->setConfig( $config );
        $this->setName( $name );
        $this->setDirection( $direction );
        $this->setMaximum( $maximum );
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
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig( Config $config )
    {
        if ( $this->config === null and $config !== null and !$config->getScores()->contains( $this )){
            $config->getScores()->add($this) ;
        }
        $this->config = $config;
    }

    public function getOptions(): Score\Options
    {
        $configOptions = new Score\Options(
            $this->getName(),
            $this->getDirection(),
            $this->getMaximum(),
            $this->getParent() ? $this->getParent()->getOptions() : null
        );
        return $configOptions;
    }
}