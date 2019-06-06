<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-6-19
 * Time: 7:55
 */

namespace Voetbal\Ranking\End;

class Item
{
    /**
     * @var int
     */
    private $uniqueRank;
    /**
     * @var int
     */
    private $rank;
    /**
     * @var string
     */
    private $name;

    /**
     * EndRankingItem constructor.
     * @param int $uniqueRank
     * @param int $rank
     * @param string $name
     */
    public function __construct( int $uniqueRank, int $rank, string $name )
    {
        $this->uniqueRank = $uniqueRank;
        $this->rank = $rank;
        $this->name = $name;
    }

    public function getUniqueRank(): int {
        return $this->uniqueRank;
    }

    public function getRank(): int {
        return $this->rank;
    }

    public function getName(): string {
        return $this->name;
    }
}