<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-3-17
 * Time: 22:17
 */

namespace Voetbal\Import;

interface ImporterInterface
{
    public function import();
}

//class Importable
//{
//    /**
//     * @var int
//     */
//    protected $id;
//
//
//    public function __construct()
//    {
//
//    }
//
//    /**
//     * Get id
//     *
//     * @return int
//     */
//    public function getId()
//    {
//        return $this->id;
//    }
//
//    /**
//     * @param $id
//     */
//    public function setId( $id )
//    {
//        $this->id = $id;
//    }
//
//
//}