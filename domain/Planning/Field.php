<?php

namespace Voetbal\Planning;

use Voetbal\Sport;

class Field
{
    /**
     * @var int
     */
    protected $number;
    /**
     * @var Sport
     */
    protected $sport;

    public function __construct( int $number, Sport $sport )
    {
        $this->number = $number;
        $this->sport = $sport;
        $sport->getFields()->add( $this );
    }

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return Sport
     */
    public function getSport(): Sport
    {
        return $this->sport;
    }
}

