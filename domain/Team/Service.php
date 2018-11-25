<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 23:03
 */

namespace Voetbal\Team;

use Voetbal\Team;
use Voetbal\Association;

class ServiceDEP
{
    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct(  )
    {

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
        return $team;
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
        $team->setName($name);
        $team->setAbbreviation($abbreviation);
        $team->setImageUrl($imageUrl);
        $team->setInfo($info);
        return $team;
    }
}