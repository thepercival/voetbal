<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Structure;

class Competition implements External\Importable
{
	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var League
	 */
	private $league;

	/**
	 * @var Season
	 */
	private $season;

    /**
     * @var \DateTimeImmutable
     */
    private $startDateTime;

	/**
	 * @var int
	 */
	private $state;

    /**
     * @var ArrayCollection
     */
    private $roundNumbers;

    /**
     * @var ArrayCollection
     */
    private $referees;

    /**
     * @var ArrayCollection
     */
    private $fields;

    const STATE_CREATED = 1;
    const STATE_PUBLISHED = 2;

    use External\ImportableTrait;

    public function __construct( League $league, Season $season )
    {
        $this->league = $league;
        $this->season = $season;
        $this->state = static::STATE_CREATED;
        $this->roundNumbers = new ArrayCollection();
        $this->referees = new ArrayCollection();
        $this->fields = new ArrayCollection();
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
     * @return League
     */
	public function getLeague()
    {
        return $this->league;
    }

    /**
     * @param League $league
     */
	public function setLeague( League $league )
	{
		$this->league = $league;
	}

    /**
     * @return Season
     */
    public function getSeason()
    {
        return $this->season;
    }

    /**
     * @param Season $season
     */
    public function setSeason( Season $season )
    {
        $this->season = $season;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getLeague()->getName() . ' ' . $this->getSeason()->getName();
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * @param \DateTimeImmutable $datetime
     */
    public function setStartDateTime( \DateTimeImmutable $datetime )
    {
        $this->startDateTime = $datetime;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState( $state )
    {
        $this->state = $state;
    }

    /**
     * @return ArrayCollection
     */
    public function getRoundNumbers()
    {
        return $this->roundNumbers;
    }

    /**
     * @return ArrayCollection
     */
    public function getReferees()
    {
        return $this->referees;
    }

    /**
     * @param $referees
     */
    public function setReferees( $referees )
    {
        $this->referees = $referees;
    }

    /**
     * @return Referee
     */
    public function getReferee( $initials )
    {
        $referees = array_filter( $this->getReferees()->toArray(), function( $referee ) use ( $initials ) {
            return $referee->getInitials() === $initials;
        });
        return array_shift( $referees );
    }

    /**
     * @return Referee
     */
    public function getRefereeById( $id )
    {
        $referees = array_filter( $this->getReferees()->toArray(), function( $referee ) use ( $id ) {
            return $referee->getId() === $id;
        });
        return array_shift( $referees );
    }

    /**
     * @return ArrayCollection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param $fields
     */
    public function setFields( $fields )
    {
        $this->fields = $fields;
    }

    /**
     * @return Field
     */
    public function getField( $number )
    {
        $fields = array_filter( $this->getFields()->toArray(), function( $field ) use ( $number ) {
            return $field->getNumber() === $number;
        });
        return array_shift( $fields );
    }

    /**
     * @return Structure
     */
    /*public function getStructure(): Structure
    {
        $firstRoundNumber = null;
        foreach( $this->getRoundNumbers() as $roundNumber ) {
            if( $roundNumber->getNumber() === 1 ) {
                $firstRoundNumber = $roundNumber;
            }
        }
        return new Structure( $firstRoundNumber, $firstRoundNumber->getRounds()->first() );
    }*/
}