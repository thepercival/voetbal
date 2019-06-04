<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 10-3-18
 * Time: 21:10
 */

namespace Voetbal\Round\Config\Score;

use Voetbal\Round\Config\Score;

trait OptionsTrait
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var int
     */
    protected $direction;
    /**
     * @var int
     */
    protected $maximum;


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName( $name )
    {
        if ( !is_string( $name ) or strlen( $name ) < Options::MIN_LENGTH_NAME or strlen( $name ) > Options::MAX_LENGTH_NAME  ){
            throw new \InvalidArgumentException( "de naam heeft een onjuiste waarde", E_ERROR );
        }
        $this->name = $name;
    }

    public function getNameSingle(): string {
        if ( strpos($this->getName(),'en') !== false ) {
            return substr( $this->getName(), 0, strlen($this->getName()) - 1);
        }
        return substr( $this->getName(), 0, strlen($this->getName()) - 1);
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
        if ( $direction !== Options::UPWARDS and $direction !== Options::DOWNWARDS ) {
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