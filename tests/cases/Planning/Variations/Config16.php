<?php

namespace Voetbal\Tests\Planning\Variations;

use Voetbal\Tests\Planning\AssertConfig;

class Config16
{
    public static function get(): array
    {
        return [
            "nrOfPoules" => [
                4 => [
                    "nrOfSports" => [
                        1 => [
                            "nrOfFields" => [
                                2 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(24, 1, 12, [3])/*,
                                        2 => new AssertConfig(20, 4, 10, [8]),
                                        3 => new AssertConfig(30, 4, 15, [12]),
                                        4 => new AssertConfig(40, 4, 20, [16]),*/
                                    ]
                                ],
                                3 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(24, 1, 8, [3])/*,
                                        2 => new AssertConfig(20, 4, 10, [8]),
                                        3 => new AssertConfig(30, 4, 15, [12]),
                                        4 => new AssertConfig(40, 4, 20, [16]),*/
                                    ]
                                ],
                                4 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(24, 1, 6, [3])/*,
                                        2 => new AssertConfig(20, 4, 10, [8]),
                                        3 => new AssertConfig(30, 4, 15, [12]),
                                        4 => new AssertConfig(40, 4, 20, [16]),*/
                                    ]
                                ],
                                6 => [
                                    "nrOfHeadtohead" => [/*
                                        1 => new AssertConfig(10, 4, 5, [4]),
                                        2 => new AssertConfig(20, 4, 10, [8]),
                                        3 => new AssertConfig(30, 4, 15, [12]),
                                        4 => new AssertConfig(40, 4, 20, [16]),*/
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}


