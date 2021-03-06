<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-6-19
 * Time: 20:16
 */
namespace Voetbal\Sport;

abstract class Custom
{
    const Min = 1;
    const Badminton = 1;
    const Basketball = 2;
    const Darts = 3;
    const ESports = 4;
    const Hockey = 5;
    const Korfball = 6;
    const Chess = 7;
    const Squash = 8;
    const TableTennis = 9;
    const Tennis = 10;
    const Football = 11;
    const Volleyball = 12;
    const Baseball = 13;
    const IceHockey = 14;
    const Max = 14;

    public static function get(): array
    {
        return [
            Custom::Badminton,
            Custom::Basketball,
            Custom::Darts,
            Custom::ESports,
            Custom::Hockey,
            Custom::Baseball,
            Custom::Korfball,
            Custom::Chess,
            Custom::Squash,
            Custom::TableTennis,
            Custom::Tennis,
            Custom::Football,
            Custom::Volleyball,
            Custom::IceHockey
        ];
    }
}
