<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-17
 * Time: 21:29
 */

namespace Voetbal\External;

trait ImportableTrait
{
    /**
     * @var ArrayCollection
     */
    protected $externals;

    public function __construct()
    {
        $this->externals = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getExternals()
    {
        return $this->externals;
    }
}