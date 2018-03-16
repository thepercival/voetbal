<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-3-18
 * Time: 11:55
 */

namespace Voetbal\Referee;

use Voetbal\Referee;
use Voetbal\Competition;
use Voetbal\Referee\Repository as RefereeRepository;

class Service
{
    /**
     * @var RefereeRepository
     */
    protected $repos;

    /**
     * Service constructor.
     *
     * @param RefereeRepository $repos
     */
    public function __construct( RefereeRepository $repos )
    {
        $this->repos = $repos;
    }

    public function create( string $initials, string $name, Competition $competition ): Referee
    {
        $refereeWithSameInitials = $this->repos->findOneBy( array('initials' => $initials ) );
        if ( $refereeWithSameInitials !== null ){
            throw new \Exception("de scheidsrechter met de initialen ".$initials." bestaat al", E_ERROR );
        }
        $referee = new Referee( $competition, $initials );
        $referee->setName($name);
        return $this->repos->save($referee);
    }

//    public function edit( Referee $referee, $name, Period $period )
//    {
//        $refereeWithSameName = $this->repos->findOneBy( array('name' => $name ) );
//        if ( $refereeWithSameName !== null and $refereeWithSameName !== $referee ){
//            throw new \Exception("het seizoen ".$name." bestaat al", E_ERROR );
//        }
//
//        $referee->setName( $name );
//        $referee->setPeriod( $period );
//
//        return $this->repos->save($referee);
//    }

    /**
     * @param Referee $referee
     */
    public function remove( Referee $referee )
    {
        $this->repos->remove($referee);
    }
}