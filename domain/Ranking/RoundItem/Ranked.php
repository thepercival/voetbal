<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-6-19
 * Time: 7:56
 */

export class RankedRoundItem {
constructor(private unrankedRoundItem: UnrankedRoundItem, private uniqueRank: number, private rank: number
) {
}

    getUniqueRank(): number {
    return this.uniqueRank;
}

    getRank(): number {
    return this.rank;
}

    getPlaceLocation(): PlaceLocation {
    return this.unrankedRoundItem.getPlaceLocation();
}

    getUnranked(): UnrankedRoundItem {
    return this.unrankedRoundItem;
}

    getPlace(): Place {
    return this.unrankedRoundItem.getRound().getPlace(this.unrankedRoundItem.getPlaceLocation());
}
}