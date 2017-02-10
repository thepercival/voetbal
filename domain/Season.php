<?php

namespace Voetbal;

use League\Period\Period;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

class Season
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $startdate;

    /**
     * @var \DateTime
     */
    private $enddate;

    /**
     * @var ArrayCollection
     */
    private $competitionseasons;

    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 9;

    public function __construct( $name, Period $period )
    {
        $this->setName( $name );
        $this->competitionseasons = new ArrayCollection();
        if ( $period === null )
            throw new \InvalidArgumentException( "de periode moet gezet zijn", E_ERROR );
        $this->startdate = $period->getStartDate();
        $this->enddate = $period->getEndDate();
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
        if ( $name === null )
            throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );

        if ( strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME ){
            throw new \InvalidArgumentException( "de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }

        if(preg_match('/[^0-9 \/-]/i', $name)){
            throw new \InvalidArgumentException( "de naam mag alleen cijfers, streeptjes, slashes en spaties bevatten", E_ERROR );
        }

        $this->name = $name;
    }

    /**
     * @return \DateTime
     */
    public function getStartdate()
    {
        return $this->startdate;
    }

    /**
     * @return \DateTime
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    public function getPeriod()
    {
       return new Period( $this->startdate, $this->enddate );
    }

    public function setPeriod( Period $period)
    {
        $this->startdate = $period->getStartDate();
        $this->enddate = $period->getEndDate();
    }

    /**
     * @return ArrayCollection
     */
    public function getCompetitionseasons()
    {
        return $this->competitionseasons;
    }
}