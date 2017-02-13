<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 7-2-2017
 * Time: 09:58
 */

namespace Voetbal\External\System;

use Voetbal\External\System;
use Voetbal\Repository\External\System as SystemRepository;

class Service
{
    /**
     * @var SystemRepository
     */
    protected $repos;

    /**
     * Service constructor.
     * @param SystemRepository $systemRepos
     */
    public function __construct( SystemRepository $systemRepos )
    {
        $this->repos = $systemRepos;
    }

    /**
     * @param $name
     * @param null $description
     * @return Association
     * @throws \Exception
     */
    public function create( $name, $website = null )
    {
        $system = new System( $name );
        $system->setWebsite($website);

        $systemWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $systemWithSameName !== null ){
            throw new \Exception("het externe systeem ".$name." bestaat al", E_ERROR );
        }

        return $this->repos->save($system);
    }

    /**
     * @param System $system
     * @param $name
     * @param $description
     * @param Association $parent
     * @throws \Exception
     */
    public function edit( System $system, $name, $website )
    {
        $systemWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $systemWithSameName !== null and $systemWithSameName !== $system ){
            throw new \Exception("het externe systeem ".$name." bestaat al", E_ERROR );
        }

        $system->setName($name);
        $system->setWebsite($website);

        return $this->repos->save($system);
    }

    /**
     * @param System $system
     *
     * @throws \Exception
     */
    public function remove( System $system )
    {
        $this->repos->remove($system);
    }
}