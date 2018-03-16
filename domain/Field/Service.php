<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-3-18
 * Time: 12:04
 */

namespace Voetbal\Field;

use Voetbal\Field;
use Voetbal\Competition;
use Voetbal\Field\Repository as FieldRepository;

class Service
{
    /**
     * @var FieldRepository
     */
    protected $repos;

    /**
     * Service constructor.
     *
     * @param FieldRepository $repos
     */
    public function __construct( FieldRepository $repos )
    {
        $this->repos = $repos;
    }

    public function create( int $number, string $name, Competition $competition ): Field
    {
        $fieldWithSameNumber = $this->repos->findOneBy(
            array(
                'number' => $number,
                'competition' => $competition,
                ) );
        if ( $fieldWithSameNumber !== null ){
            throw new \Exception("het veld met nummer ".$number." bestaat al", E_ERROR );
        }
        $field = new Field( $competition, $number, $name );
        return $this->repos->save($field);
    }

//    public function edit( Field $field, $name, Period $period )
//    {
//        $fieldWithSameName = $this->repos->findOneBy( array('name' => $name ) );
//        if ( $fieldWithSameName !== null and $fieldWithSameName !== $field ){
//            throw new \Exception("het seizoen ".$name." bestaat al", E_ERROR );
//        }
//
//        $field->setName( $name );
//        $field->setPeriod( $period );
//
//        return $this->repos->save($field);
//    }

    /**
     * @param Field $field
     */
    public function remove( Field $field )
    {
        $this->repos->remove($field);
    }
}