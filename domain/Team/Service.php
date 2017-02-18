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

class Service
{
    /**
     * @var TeamRepository
     */
    protected $repos;

    /**
     * Service constructor.
     *
     * @param TeamRepository $teamRepos
     */
    public function __construct( TeamRepository $repos )
    {
        $this->repos = $repos;
    }

    /**
     * @param $name
     * @param null $description
     * @param Association|null $parent
     * @return Association
     * @throws \Exception
     */
    public function create( $name, $description = null, Association $parent = null )
    {
        $association = new Association( $name );
        $association->setDescription($description);
        $association->setParent($parent);

        $associationWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $associationWithSameName !== null ){
            throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
        }

        return $this->repos->save($association);
    }

    /**
     * @param Association $association
     * @param $name
     * @param $description
     * @param Association $parent
     * @throws \Exception
     */
    public function edit( Association $association, $name, $description, Association $parent = null )
    {
        $associationWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $associationWithSameName !== null and $associationWithSameName !== $association ){
            throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
        }

        $association->setName($name);
        $association->setDescription($description);
        $association->setParent($parent);

        return $this->repos->save($association);
    }

    /**
     * @param Association $association
     */
    public function remove( Association $association )
    {
        $this->repos->remove($association);
    }
}