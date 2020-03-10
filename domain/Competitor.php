<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 13:19
 */

namespace Voetbal;

use Voetbal\Import\Idable as Importable;

class Competitor implements Importable
{
    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 30;
    const MAX_LENGTH_ABBREVIATION = 3;
    const MAX_LENGTH_INFO = 200;
    const MAX_LENGTH_IMAGEURL = 150;
    /**
     * @var int|string
     */
    protected $id;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $abbreviation;
    /**
     * @var bool
     */
    protected $registered;
    /**
     * @var string
     */
    protected $info;
    /**
     * @var string
     */
    protected $imageUrl;
    /**
     * @var Association
     */
    protected $association;

    use ImportableTrait;

    public function __construct( Association $association, string $name )
    {
        $this->setAssociation( $association );
        $this->setName( $name );
        $this->setRegistered(false);
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     * @return void
     */
    public function setId( $id )
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName( $name )
    {
        if ( strlen( $name ) === 0 )
            throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );

        if ( strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME ){
            throw new \InvalidArgumentException( "de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * @param string $abbreviation
     */
    public function setAbbreviation( $abbreviation )
    {
        if ( strlen($abbreviation) === 0 ){
            $abbreviation = null;
        }

        if ( strlen( $abbreviation ) > static::MAX_LENGTH_ABBREVIATION ){
            throw new \InvalidArgumentException( "de afkorting mag maximaal ".static::MAX_LENGTH_ABBREVIATION." karakters bevatten", E_ERROR );
        }
        $this->abbreviation = $abbreviation;
    }

    /**
     * @return bool
     */
    public function getRegistered()
    {
        return $this->registered;
    }

    /**
     * @param bool $registered
     */
    public function setRegistered( $registered )
    {
        $this->registered = $registered;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string $info
     */
    public function setInfo( $info)
    {
        if ( strlen($info) === 0 ){
            $info = null;
        }

        if ( strlen( $info ) > static::MAX_LENGTH_INFO ){
            throw new \InvalidArgumentException( "de extra-info mag maximaal ".static::MAX_LENGTH_INFO." karakters bevatten", E_ERROR );
        }
        $this->info = $info;
    }

    /**
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * @param string $imageUrl
     */
    public function setImageUrl( $imageUrl)
    {
        if ( strlen($imageUrl) === 0 ){
            $imageUrl = null;
        }

        if ( strlen( $imageUrl ) > static::MAX_LENGTH_IMAGEURL ){
            throw new \InvalidArgumentException( "de imageUrl mag maximaal ".static::MAX_LENGTH_IMAGEURL." karakters bevatten", E_ERROR );
        }
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return Association
     */
    public function getAssociation()
    {
        return $this->association;
    }

    /**
     * @param Association $association
     */
    public function setAssociation( Association $association )
    {
        if ( !$association->getCompetitors()->contains( $this )){
            $association->getCompetitors()->add($this) ;
        }
        $this->association = $association;
    }
}