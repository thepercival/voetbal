<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 7-2-2017
 * Time: 09:58
 */

namespace Voetbal\External\System;

use Voetbal\External\System;
use Voetbal\External\System\Repository as SystemRepository;

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
     * @param null $website
     * @param null $username
     * @param null $password
     * @param null $apiurl
     * @param null $apikey
     * @return mixed
     * @throws \Exception
     */
    public function create( $name, $website = null, $username = null, $password = null, $apiurl = null, $apikey = null )
    {
        $systemWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $systemWithSameName !== null ){
            throw new \Exception("het externe systeem ".$name." bestaat al", E_ERROR );
        }

        $system = new System( $name );
        $system->setWebsite($website);
        $system->setUsername($username);
        $system->setPassword($password);
        $system->setApiurl($apiurl);
        $system->setApikey($apikey);

        return $this->repos->save($system);
    }

    /**
     * @param System $system
     * @param $data
     * @throws \Exception
     */
    public function edit( System $system, $data )
    {
        $name = $data['name'];
        $website = $data['website'];
        $username = $data['username'];
        $password = $data['password'];
        $apiurl = $data['apiurl'];
        $apikey = $data['apikey'];

        $systemWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $systemWithSameName !== null and $systemWithSameName !== $system ){
            throw new \Exception("het externe systeem ".$name." bestaat al", E_ERROR );
        }

        $system->setName($name);
        $system->setWebsite($website);
        $system->setUsername($username);
        $system->setPassword($password);
        $system->setApiurl($apiurl);
        $system->setApikey($apikey);

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