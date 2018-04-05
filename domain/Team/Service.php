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
    public function create( string $name, Association $association,
        string $abbreviation = null, string $imageUrl = null, string $info = null )
    {
        $team = new Team( $name, $association );
        $team->setAbbreviation($abbreviation);
        $team->setImageUrl($imageUrl);
        $team->setInfo($info);

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
     * @param string $name
     * @param string|null $abbreviation
     * @param string|null $imageUrl
     * @param string|null $info
     * @return mixed
     */
    public function edit( Team $team, string $name,
        string $abbreviation = null, string $imageUrl = null, string $info = null )
    {
        // could be a setting to check this
//        $teamWithSameName = $this->repos->findOneBy( array('name' => $name ) );
//        if ( $teamWithSameName !== null and $teamWithSameName !== $team ){
//            throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
//        }

        $team->setName($name);
        $team->setAbbreviation($abbreviation);
        $team->setImageUrl($imageUrl);
        $team->setInfo($info);

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