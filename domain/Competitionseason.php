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
	 * @var int
	 */
	private $qualificationrule;

    /**
     * @var Association
     */
    private $association;

    /**
     * @var ArrayCollection
     */
    private $rounds;

    const STATE_CREATED = 1;
    const STATE_PUBLISHED = 2;

    const QUALIFICATION_RULE_WC = 1;
    const QUALIFICATION_RULE_EC = 2; // max size

    use External\ImportableTrait;

    public function __construct( Competition $competition, Season $season, Association $association )
    {
        $this->competition = $competition;
        $this->season = $season;
        $this->association = $association;
        $this->state = static::STATE_CREATED;
        $this->qualificationrule = static::QUALIFICATION_RULE_WC;
        $this->rounds = new ArrayCollection();
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
     * @return int
     */
    public function getQualificationrule()
    {
        return $this->qualificationrule;
    }

	/**
	 * @param int $qualificationrule
	 */
    public function setQualificationrule( $qualificationrule )
    {
    	if ( is_int( $qualificationrule ) or $qualificationrule < static::QUALIFICATION_RULE_WC or $qualificationrule > static::QUALIFICATION_RULE_EC  ){
		    throw new \InvalidArgumentException( "de kwalificatieregel heeft een onjuiste waarde", E_ERROR );
	    }
        $this->qualificationrule = $qualificationrule;
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
}