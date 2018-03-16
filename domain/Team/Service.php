<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 23:03
 */

namespace Voetbal\Team;

use Voetbal\Team;
use Voetbal\Team\Repository as TeamRepository;
use Voetbal\Association;

class Service
{
    /**
     * @var TeamRepository
     */
    protected $repos;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( TeamRepository $repos )
    {
        $this->repos = $repos;
    }

    /**
     * @param $name
     * @param Association $association
     * @param null $abbreviation
     * @return mixed
     * @throws \Exception
     */
    public function create( $name, Association $association, $abbreviation = null, $imageUrl = null )
    {
        $team = new Team( $name, $association );
        $team->setAbbreviation($abbreviation);
        $team->setImageUrl($imageUrl);

        // could be a settings to check this!
//        $teamWithSameName = $this->repos->findOneBy(
//            array( 'name' => $name, 'association' => $association )
//        );
//        if ( $teamWithSameName !== null ){
//            throw new \Exception("de teamnaam ".$name." bestaat al", E_ERROR );
//        }

        return $this->repos->save($team);
    }

    /**
     * @param Team $team
     * @param $name
     * @param Association $association
     * @param null $abbreviation
     * @return mixed
     * @throws \Exception
     */
    public function edit( Team $team, $name, Association $association, $abbreviation = null, $imageUrl = null )
    {
        // could be a setting to check this
//        $teamWithSameName = $this->repos->findOneBy( array('name' => $name ) );
//        if ( $teamWithSameName !== null and $teamWithSameName !== $team ){
//            throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
//        }

        $team->setName($name);
        $team->setAbbreviation($abbreviation);
        $team->setImageUrl($imageUrl);
        $team->setAssociation($association);

        return $this->repos->save($team);
    }

    /**
     * @param Team $team
     */
    public function remove( Team $team )
    {
        // team can only be removed if it is not in a pouleplace
        // @TODO TEST
        $this->repos->remove($team);
    }
}