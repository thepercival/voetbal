<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 30-6-19
 * Time: 12:49
 */

namespace Voetbal\Sport;

trait IdSer {
    /**
     * @var int
     */
    protected $sportIdSer;

    /**
     * @return int
     */
    public function getSportIdForSer(): int
    {
        return $this->sport->getId();
    }

    /**
     * @return int
     */
    public function getSportIdSer(): int
    {
        if( $this->sport !== null && $this->sportIdSer === null ) {
            return $this->getSportIdForSer();
        }
        return $this->sportIdSer;
    }

    /**
     * @return int
     */
    public function setSportIdSer( int $sportIdSer)
    {
        return $this->sportIdSer = $sportIdSer;
    }
}
