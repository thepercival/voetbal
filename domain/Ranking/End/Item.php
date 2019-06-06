<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-6-19
 * Time: 7:55
 */

export class EndRankingItem {
constructor(private uniqueRank: number, private rank: number, private name: string) {
}

getUniqueRank(): number {
    return this.uniqueRank;
}

    getRank(): number {
    return this.rank;
}

    getName(): string {
    return this.name;
}
}
}