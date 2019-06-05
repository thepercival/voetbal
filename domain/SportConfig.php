<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-3-18
 * Time: 9:43
 */

namespace Voetbal;

class SportConfig
{
    CONST TableTennis = 'tafeltennis';
    CONST Football = 'voetbal';
    CONST Chess = 'schaken';
    CONST Darts = 'darten';
    CONST Tennis = 'tennis';
    CONST Volleyball = 'volleybal';
    CONST Badminton = 'badminton';
    CONST Hockey = 'hockey';
    CONST Korfball = 'korfbal';
    CONST Squash = 'squash';

    protected static $useExternal = false;

    protected static function getSports(): array {
        return [
            static::TableTennis,
            static::Football,
            static::Chess,
            static::Darts,
            static::Tennis,
            static::Volleyball,
            static::Badminton,
            static::Hockey,
            static::Korfball,
            static::Squash
        ];
    }
}