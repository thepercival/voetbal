<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 7-2-2017
 * Time: 09:58
 */

namespace Voetbal\ExternalSource;

use Voetbal\ExternalSource;
use Voetbal\ExternalSource\Repository as ExternalSourceRepository;

class ServiceOldExternalSource
{
    /**
     * @var ExternalSourceRepository
     */
    protected $repos;

    public function __construct(ExternalSourceRepository $systemRepos)
    {
        $this->repos = $systemRepos;
    }

    /**
     * @param string $name
     * @param string $website
     * @param string $username
     * @param string $password
     * @param string $apiurl
     * @param string $apikey
     * @return ExternalSource
     * @throws \Exception
     */
    public function create(string $name, string $website = null, string $username = null, string $password = null, string $apiurl = null, string $apikey = null): System
    {
        $systemWithSameName = $this->repos->findOneBy(array('name' => $name ));
        if ($systemWithSameName !== null) {
            throw new \Exception("het externe systeem ".$name." bestaat al", E_ERROR);
        }

        $system = new ExternalSource($name);
        $system->setWebsite($website);
        $system->setUsername($username);
        $system->setPassword($password);
        $system->setApiurl($apiurl);
        $system->setApikey($apikey);

        return $this->repos->save($system);
    }

    /**
     * @param ExternalSource $externalSource
     * @param array $data
     * @throws \Exception
     */
    public function edit(ExternalSource $externalSource, array $data)
    {
        $name = $data['name'];
        $website = $data['website'];
        $username = $data['username'];
        $password = $data['password'];
        $apiurl = $data['apiurl'];
        $apikey = $data['apikey'];

        $sourceWithSameName = $this->repos->findOneBy(array('name' => $name ));
        if ($sourceWithSameName !== null and $sourceWithSameName !== $externalSource) {
            throw new \Exception("het externe systeem ".$name." bestaat al", E_ERROR);
        }

        $externalSource->setName($name);
        $externalSource->setWebsite($website);
        $externalSource->setUsername($username);
        $externalSource->setPassword($password);
        $externalSource->setApiurl($apiurl);
        $externalSource->setApikey($apikey);

        return $this->repos->save($externalSource);
    }

    /**
     * @param ExternalSource $externalSource
     */
    public function remove(ExternalSource $externalSource)
    {
        $this->repos->remove($externalSource);
    }
}
