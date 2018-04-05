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

    public function create( Competition $competition, string $initials, string $name = null, string $info = null ): Referee
    {
        $refereeWithSameInitials = $this->repos->findOneBy(
            array( 'initials' => $initials, 'competition' => $competition )
        );
        if ( $refereeWithSameInitials !== null ){
            throw new \Exception("de scheidsrechter met de initialen ".$initials." bestaat al", E_ERROR );
        }
        $referee = new Referee( $competition, $initials );
        $referee->setName($name);
        $referee->setInfo( $info );
        return $this->repos->save($referee);
    }

    public function edit( Referee $referee, string $initials, string $name = null, string $info = null ): Referee
    {
        $refereeWithSameInitials = $this->repos->findOneBy(
            array( 'initials' => $initials, 'competition' => $referee->getCompetition() )
        );
        if ( $refereeWithSameInitials !== null and $refereeWithSameInitials !== $referee ){
            throw new \Exception("de scheidsrechter met de initialen ".$initials." bestaat al", E_ERROR );
        }

        $referee->setInitials( $initials );
        $referee->setName( $name );
        $referee->setInfo( $info );

        return $this->repos->save($referee);
    }

    /**
     * @param Referee $referee
     */
    public function remove( Referee $referee )
    {
        $this->repos->remove($referee);
    }
}