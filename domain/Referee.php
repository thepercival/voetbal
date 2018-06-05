<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-10-17
 * Time: 15:42
 */

namespace Voetbal;

/**
 * Class Referee
 * @package Voetbal
 */
class Referee
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $initials;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $emailaddress;

    /**
     * @var string
     */
    private $info;

    /**
     * @var Competition
     */
    private $competition;

    const MIN_LENGTH_INITIALS = 1;
    const MAX_LENGTH_INITIALS = 3;
    const MIN_LENGTH_NAME = 1;
    const MAX_LENGTH_NAME = 15;
    const MIN_LENGTH_EMAIL = 6;
    const MAX_LENGTH_EMAIL = 100;
    const MAX_LENGTH_INFO = 200;

    public function __construct( Competition $competition, $initials )
    {
        $this->setInitials( $initials );
        $this->setCompetition( $competition );
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
     * @return string
     */
    public function getInitials()
    {
        return $this->initials;
    }

    /**
     * @param string
     */
    public function setInitials( $initials )
    {
        if ( $initials === null ) {
            throw new \InvalidArgumentException( "de initialen moet gezet zijn", E_ERROR );
        }
        if ( strlen( $initials ) < static::MIN_LENGTH_INITIALS or strlen( $initials ) > static::MAX_LENGTH_INITIALS ){
            throw new \InvalidArgumentException( "de initialen moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }
        if(!ctype_alnum($initials)){
            throw new \InvalidArgumentException( "de initialen (".$initials.") mag alleen cijfers en letters bevatten", E_ERROR );
        }
        $this->initials = $initials;
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
        if ( $name !== null && ( strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME ) ){
            throw new \InvalidArgumentException( "de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }
        if( $name !== null && !preg_match('/^[a-z0-9 .\-]+$/i', $name)){
            throw new \InvalidArgumentException( "de naam (".$name.") mag alleen cijfers, streeptjes, slashes en spaties bevatten", E_ERROR );
        }
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEmailaddress()
    {
        return $this->emailaddress;
    }

    /**
     * @param string $emailaddress
     */
    public function setEmailaddress( $emailaddress )
    {
        if( strlen( $emailaddress ) > 0 ) {
            if (strlen($emailaddress) < static::MIN_LENGTH_EMAIL or strlen($emailaddress) > static::MAX_LENGTH_EMAIL) {
                throw new \InvalidArgumentException("het emailadres moet minimaal " . static::MIN_LENGTH_EMAIL . " karakters bevatten en mag maximaal " . static::MAX_LENGTH_EMAIL . " karakters bevatten", E_ERROR);
            }

            if (!filter_var($emailaddress, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("het emailadres " . $emailaddress . " is niet valide", E_ERROR);
            }
        }
        $this->emailaddress = $emailaddress;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string
     */
    public function setInfo( $info )
    {
        if ( strlen( $info ) > static::MAX_LENGTH_INFO ){
            $info = substr( $info, 0, static::MAX_LENGTH_INFO );
        }
        $this->info = $info;
    }

    /**
     * @return Competition
     */
    public function getCompetition()
    {
        return $this->competition;
    }

    /**
     * @param Competition $Competition
     */
    public function setCompetition( Competition $competition )
    {
        $this->competition = $competition;
    }
}