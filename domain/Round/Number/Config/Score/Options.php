<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 10-3-18
 * Time: 21:10
 */

namespace Voetbal\Round\Number\Config\Score;

class Options
{
    use OptionsTrait;

    /**
     * @var Options
     */
    protected $parent;
    /**
     * @var Options
     */
    protected $child;

    const MIN_LENGTH_NAME = 3;
    const MAX_LENGTH_NAME = 10;

    const UPWARDS = 1;
    const DOWNWARDS = 2;

    public function __construct( string $name, int $direction, int $maximum, Options $parent = null )
    {
        $this->setName( $name );
        $this->setDirection( $direction );
        $this->setMaximum( $maximum );
        $this->setParent( $parent );
    }

    /**
     * @return Options
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Options $scoreConfig
     */
    public function setParent( Options $scoreConfig = null )
    {
        $this->parent = $scoreConfig;
    }

    /**
     * @return Options
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @param Options $scoreConfig
     */
    protected function setChild( Options $scoreConfig = null )
    {
        $this->child = $scoreConfig;
    }


}