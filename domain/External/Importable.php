<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-2-17
 * Time: 12:06
 */

namespace Voetbal;

abstract class Importable
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