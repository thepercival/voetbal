<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

class Competitionseason implements External\Importable
{
	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var Competition
	 */
	private $competition;

	/**
	 * @var Season
	 */
	private $season;

	/**
	 * @var int
	 */
	private $state;

    /**
     * @var Association
     */
    private $association;

    /**
     * @var string
     */
    private $sport;

    /**
     * @var int
     */
    private $nrOfFields;

    /**
     * @var ArrayCollection
     */
    private $rounds;

    /**
     * @var ArrayCollection
     */
    private $referees;

    const STATE_CREATED = 1;
    const STATE_PUBLISHED = 2;

    use External\ImportableTrait;

    public function __construct( Competition $competition, Season $season, Association $association )
    {
        $this->competition = $competition;
        $this->season = $season;
        $this->association = $association;
        $this->state = static::STATE_CREATED;
        $this->nrOfFields = 1;
        $this->rounds = new ArrayCollection();
        $this->referees = new ArrayCollection();
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
     * @return Competition
     */
	public function getCompetition()
    {
        return $this->competition;
    }

    /**
     * @param Competition $competition
     */
	public function setCompetition( Competition $competition )
	{
		$this->competition = $competition;
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
     * @return int
     */
    public function getState()
    {
        return $this->state;
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
    public function setAssociation( $association )
    {
        $this->association = $association;
    }

    /**
     * @return ArrayCollection
     */
    public function getRounds()
    {
        return $this->rounds;
    }

    /**
     * @return ArrayCollection
     */
    public function getReferees()
    {
        return $this->referees;
    }

    /**
     * @return Round
     */
    public function getFirstRound()
    {
        foreach( $this->getRounds() as $round ) {
            if( $round->getNumber() === 1 ) {
                return $round;
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getSport()
    {
        return $this->sport;
    }

    /**
     * @param string $sport
     */
    public function setSport( $sport )
    {
        $this->sport = $sport;
    }

    /**
     * @return int
     */
    public function getNrOfFields()
    {
        return $this->nrOfFields;
    }

    /**
     * @param int $nrOfFields
     */
    public function setNrOfFields( $nrOfFields )
    {
        $this->nrOfFields = $nrOfFields;
    }

    /**
     * @return bool
     */
    public function hasGames()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasPlayedGames()
    {
        return true;
    }
}