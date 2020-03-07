<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-3-17
 * Time: 22:17
 */

namespace Voetbal\Import;


interface Idable
{
    public function getId(): ?int;
    public function setId( int $id = null );
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