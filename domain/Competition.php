<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\Common\Collections\Collection;
use \Doctrine\ORM\PersistentCollection;
use Voetbal\Ranking\Service as RankingService;
use Voetbal\Sport\Config as SportConfig;
use Voetbal\Import\Idable as Importable;

class Competition implements Importable
{
	/**
	 * @var int|string
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
    private $ruleSet;

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
    private $sportConfigs;

    /**
     * @var ArrayCollection
     */
    private $fields;

    const MIN_COMPETITORS = 3;
    const MAX_COMPETITORS = 40;

    use ImportableTrait;

    public function __construct( League $league, Season $season )
    {
        $this->league = $league;
        $this->season = $season;
        $this->ruleSet = RankingService::RULESSET_WC;
        $this->state = State::Created;
        $this->roundNumbers = new ArrayCollection();
        $this->referees = new ArrayCollection();
        $this->fields = new ArrayCollection();
        $this->sportConfigs = new ArrayCollection();
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
    public function getRuleSet()
    {
        return $this->ruleSet;
    }

    /**
     * @param int $ruleSet
     */
    public function setRuleSet( $ruleSet )
    {
        $this->ruleSet = $ruleSet;
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
     * @return ArrayCollection | Referee[]
     */
    public function getReferees()
    {
        return $this->referees;
    }

    /**
     * @param ArrayCollection | Referee[] $referees
     */
    public function setReferees( $referees )
    {
        $this->referees = $referees;
    }

    /**
     * @return Referee
     */
    public function getReferee( int $rank )
    {
        $referees = array_filter( $this->getReferees()->toArray(), function( $referee ) use ( $rank ) {
            return $referee->getRank() === $rank;
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
     * @return ArrayCollection | Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param ArrayCollection | Field[] $fields
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

    public function setSportConfigs(ArrayCollection $sportConfigs)
    {
        $this->sportConfigs = $sportConfigs;
    }

    /**
     * @return ArrayCollection | PersistentCollection | SportConfig[]
     */
    public function getSportConfigs() {
        return $this->sportConfigs;
    }

    public function getSportConfig(Sport $sport = null): ?SportConfig {
        $foundConfigs = $this->sportConfigs->filter( function ($sportConfig) use ( $sport ) {
            return $sportConfig->getSport() === $sport;
        });
        $foundConfig = $foundConfigs->first();
        return $foundConfig !== false ? $foundConfig : null;
    }

    public function setSportConfig(SportConfig $sportConfig) {
        $this->sportConfigs->add( $sportConfig );
    }

    public function hasMultipleSportConfigs(): bool {
        return $this->sportConfigs->count() > 1;
    }

    public function getFirstSportConfig(): SportConfig {
        return $this->sportConfigs[0];
    }

    public function getSportBySportId( int $sportId): ?Sport
    {
        foreach( $this->getSportConfigs() as $sportConfig ) {
            if( $sportConfig->getSport()->getId() === $sportId) {
                return $sportConfig->getSport();
            }
        }
        return null;
    }

    /**
     * @return Collection | Sport[]
     */
    public function getSports(): Collection {
        return $this->sportConfigs->map( function ($sportConfig) { return $sportConfig->getSport(); });
    }
}