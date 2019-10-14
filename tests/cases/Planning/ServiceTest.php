<?php

use Voetbal\Planning\GameGenerator;
use Voetbal\Structure\Service as StructureService;

/**
 * Created by PhpStorm.
 * User: coen
 * Date: 10-6-19
 * Time: 20:25
 */

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testVariations()
    {
        $maxNrOfCompetitors = 16;
        $maxNrOfSports = 1;

        for ($nrOfCompetitors = 2; $nrOfCompetitors <= $maxNrOfCompetitors; $nrOfCompetitors++) {
            $nrOfPoules = 0;
            // let teamup = false;
            // let selfReferee = false;
            $maxNrOfHeadtohead = 4;
            while ( ((int) floor($nrOfCompetitors / ++$nrOfPoules)) >= 2) {
                for ($nrOfSports = 1; $nrOfSports <= $maxNrOfSports; $nrOfSports++) {
                    for ($nrOfFields = $nrOfSports; $nrOfFields <= $nrOfSports * 2; $nrOfFields++) {
                        for ($nrOfHeadtohead = 1; $nrOfHeadtohead <= $maxNrOfHeadtohead; $nrOfHeadtohead++) {
                            // if (nrOfCompetitors !== 4 || nrOfPoules !== 1
                            //     || nrOfSports !== 3 || nrOfFields !== 4 || nrOfHeadtohead !== 3) {
                            //     continue;
                            // }

                            $assertConfig = getAssertionsConfig($nrOfCompetitors, $nrOfPoules, $nrOfSports, $nrOfFields, $nrOfHeadtohead);
//                            console.log(
//                                'nrOfCompetitors ' + nrOfCompetitors + ', nrOfPoules ' + nrOfPoules + ', nrOfSports ' + nrOfSports
//                                + ', nrOfFields ' + nrOfFields + ', nrOfHeadtohead ' + nrOfHeadtohead
//                            );
                            if ($assertConfig !== null) {
                                checkPlanning($nrOfCompetitors, $nrOfPoules, $nrOfSports, $nrOfFields, $nrOfHeadtohead, $assertConfig);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * check if every place has the same amount of games
     * check if one place is not two times in one game
     * for planning : add selfreferee if is this enables
     *
     * @param Place $place
     * @param array|Game[] $games
     * @param int|null $expectedValue
     */
    protected function assertValidGamesParticipations(Place $place, array $games, int $expectedValue = null) {
        $sportPlanningConfigService = new SportPlanningConfigService();
        $nrOfGames = 0;
        foreach( $games as $game ) {
            $nrOfSingleGameParticipations = 0;
            $places = $game->getPlaces()->map( function( $gamePlace ) { return $gamePlace->getPlace(); } );
            foreach( $places as $placeIt ) {
                if ($placeIt === $place) {
                    $nrOfSingleGameParticipations++;
                }
            }
            if ($nrOfSingleGameParticipations === 1) {
                $nrOfGames++;
            }
            if ($game->getRefereePlace() && $game->getRefereePlace() === $place) {
                $nrOfSingleGameParticipations++;
            }
            $this->assertLessThan( 2, $nrOfSingleGameParticipations);
        }
        $config = $place->getRound()->getNumber()->getValidPlanningConfig();
        // const nrOfGamesPerPlace = sportPlanningConfigService.getNrOfGamesPerPlace(place.getPoule(), config.getNrOfHeadtohead());
        // expect(nrOfGamesPerPlace).to.equal(nrOfGames);
        if ($expectedValue !== null) {
            $this->assertSame( $expectedValue, $nrOfGames, 'nrofgames for 1 place are not equal');
        }
    }

    /**
     * @param Place $place
     * @param array|Game[] $games
     * @param int $maxInRow
     */
    protected function assertGamesInRow(Place $place, array $games, int $maxInRow ) {
        $batches = []; $maxBatchNr = 0;
        foreach( $games as $game ) {
            if ( array_key_exists( $game->getResourceBatch(), $batches ) === false ) {
                $batches[$game->getResourceBatch()] = false;
                if ($game->getResourceBatch() > $maxBatchNr) {
                    $maxBatchNr = $game->getResourceBatch();
                }
            }
            if ( array_key_exists( $game->getResourceBatch(), $batches ) ) {
                return;
            }
            $places = $game->getPlaces()->map( function( $gamePlace ) { return $gamePlace->getPlace(); } );
            $some = false;
            array_walk( $places, function( $placeIt ) use ( $place ) {
                if( $placeIt === $place ) {
                    $some = true;
                }
            });
            $batches[$game->getResourceBatch()] = $some;
        }
        $nrOfGamesInRow = 0;
        for ($i = 1; $i <= $maxBatchNr; $i++) {
            if ($batches[$i]) {
                $nrOfGamesInRow++;
                $this->assertLessThan( $maxInRow + 1, $nrOfGamesInRow);
            } else {
                $nrOfGamesInRow = 0;
            }
        }
    }

    /**
     * check if every batch has no double fields, referees or place
     *
     * @param array|Game[] $games
     */
    protected function assertValidResourcesPerBatch(array $games) {
        $batchResources = [];
        foreach( $games as $game ) {
            if ( array_key_exists( $game->getResourceBatch(), $batchResources ) === false ) {
                $batchResources[$game->getResourceBatch()] = { fields: [], referees: [], places: [] };
            }
            $batchResource = $batchResources[$game->getResourceBatch()];
            $places = $game->getPlaces()-> map( function( $gamePlace ) { return $gamePlace->getPlace(); } );
            if ($game->getRefereePlace() !== null) {
                $places[] = $game->getRefereePlace();
            }
            foreach( $places as $placeIt ) {
                expect(batchResource.places.find(place => place === placeIt)).to.equal(undefined);
                $batchResource.places[] = $placeIt;
            }
            expect(batchResource.fields.find(field => field === game.getField()), 'same field in one batch? ').to.equal(undefined);
            batchResource.fields.push(game.getField());
            if (game.getReferee()) {
                expect(batchResource.referees.find(referee => referee === game.getReferee())).to.equal(undefined);
                batchResource.referees.push(game.getReferee());
            }
        }
    }

    protected function addSport(Competition $competition ) {
        $sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
        $id = count( $competition->getSportConfigs() ) + 1;
        $sport = new Sport('sport' + id);
        $sport.setId($id);
        $sportConfigService->createDefault($sport, $competition);
        return $sport;
    }



protected function checkPlanning(
    nrOfCompetitors: number,
    nrOfPoules: number,
    nrOfSports: number,
    nrOfFields: number,
    nrOfHeadtohead: number,
    assertConfig: AssertConfig
) {
    const competitionMapper = getMapper('competition');
    const competition = competitionMapper.toObject(jsonCompetition);
    const competitionFirstSports = [];
    for (let sportNr = 2; sportNr <= nrOfSports; sportNr++) {
        competitionFirstSports.push(addSport(competition));
    }
    const competitionSports = competition.getSportConfigs().map(sportConfig => sportConfig.getSport());

    let sports = [];
    while (sports.length < nrOfFields) {
        const init = sports.length === 0;
        sports = sports.concat(competitionSports);
        if (init && competitionSports.length > 1) {
            sports.shift();
        }
    }
    for (let fieldNr = 2; fieldNr <= nrOfFields; fieldNr++) {
        const field = new Field(competition, fieldNr);
        field.setSport(sports.shift());
    }

    const structureService = new StructureService();
    const structure = structureService.create(competition, nrOfCompetitors, nrOfPoules);
    const firstRoundNumber = structure.getFirstRoundNumber();
    firstRoundNumber.getValidPlanningConfig().setNrOfHeadtohead(nrOfHeadtohead);

    const planningService = new PlanningService(competition);

    if (nrOfCompetitors === 2 && nrOfSports === 1 && nrOfFields === 1 && nrOfHeadtohead === 1) {
        const x = 1;
    }

    planningService.create(firstRoundNumber);
    const games = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    consoleGames(games); console.log('');
    expect(games.length, 'het aantal wedstrijd voor de hele ronde komt niet overeen').to.equal(assertConfig.nrOfGames);
    assertValidResourcesPerBatch(games);
    firstRoundNumber.getPlaces().forEach(place => {
        assertValidGamesParticipations(place, games, assertConfig.nrOfPlaceGames);
        assertGamesInRow(place, games, assertConfig.maxNrOfGamesInARow);
    });
    expect(games.pop().getResourceBatch(), 'het aantal batches moet minder zijn dan ..').to.be.lessThan(assertConfig.maxNrOfBatches + 1);
}

protected function getAssertionsConfig(
    nrOfCompetitors: number,
    nrOfPoules: number,
    nrOfSports: number,
    nrOfFields: number,
    nrOfHeadtohead: number
): AssertConfig {
    const competitors = {
        2: poules2,
        3: poules3,
        4: poules4
    };
    if (competitors[nrOfCompetitors] === undefined) {
        return undefined;
    }
    const nrOfCompetitorsConfigs = competitors[nrOfCompetitors];
    if (nrOfCompetitorsConfigs.nrOfPoules[nrOfPoules] === undefined) {
        return undefined;
    }
    const nrOfPoulesConfigs = nrOfCompetitorsConfigs.nrOfPoules[nrOfPoules];
    if (nrOfPoulesConfigs.nrOfSports[nrOfSports] === undefined) {
        return undefined;
    }
    const nrOfSportsConfigs = nrOfPoulesConfigs.nrOfSports[nrOfSports];
    if (nrOfSportsConfigs.nrOfFields[nrOfFields] === undefined) {
        return undefined;
    }
    const nrOfFieldsConfigs = nrOfSportsConfigs.nrOfFields[nrOfFields];
    if (nrOfFieldsConfigs.nrOfHeadtohead[nrOfHeadtohead] === undefined) {
        return undefined;
    }
    return nrOfFieldsConfigs.nrOfHeadtohead[nrOfHeadtohead];
}


    // it('game creation default', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 9, 3);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     expect(firstRoundNumber.getGames().length).to.equal(9);
    //     assertValidResourcesPerBatch(firstRoundNumber.getGames());
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, firstRoundNumber.getGames(), 2);
    //     });
    // });

    // // with one poule referee can be from same poule
    // it('self referee 1 poule of 3', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const field2 = new Field(competition, 2); field2.setSport(competition.getFirstSportConfig().getSport());

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 3, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();
    //     firstRoundNumber.getPlanningConfig().setSelfReferee(true);

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);

    //     expect(games.length).to.equal(3);
    //     const firstGame = games.shift();
    //     expect(firstGame.getResourceBatch()).to.equal(1);
    //     expect(firstGame.getRefereePlace()).to.equal(firstGame.getPoule().getPlace(1));
    //     expect(games.shift().getResourceBatch()).to.equal(2);
    //     expect(games.shift().getResourceBatch()).to.equal(3);

    //     assertValidResourcesPerBatch(firstRoundNumber.getGames());
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, firstRoundNumber.getGames(), 2);
    //     });
    // });

    // // games should be ordered by roundnumber, subnumber because if sorted by poule
    // // the planning is not optimized.
    // // If all competitors of poule A play first and there are still fields free
    // // than they cannot be referee. This will be most bad when there are two poules.
    // it('self referee 4 fields, 66', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const field2 = new Field(competition, 2); field2.setSport(competition.getFirstSportConfig().getSport());
    //     const field3 = new Field(competition, 3); field3.setSport(competition.getFirstSportConfig().getSport());
    //     const field4 = new Field(competition, 4); field4.setSport(competition.getFirstSportConfig().getSport());

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 12, 2);
    //     const firstRoundNumber = structure.getFirstRoundNumber();
    //     firstRoundNumber.getPlanningConfig().setSelfReferee(true);

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games);
    //     expect(games.length).to.equal(30);

    //     expect(games.shift().getResourceBatch()).to.equal(1);
    //     expect(games.shift().getResourceBatch()).to.equal(1);
    //     expect(games.shift().getResourceBatch()).to.equal(1);
    //     expect(games.shift().getResourceBatch()).to.equal(1);
    //     expect(games.pop().getResourceBatch()).to.be.lessThan(9);

    //     assertValidResourcesPerBatch(firstRoundNumber.getGames());
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, firstRoundNumber.getGames(), 5);
    //     });
    // });


    // it('2 fields 2 sports, 5->(3)', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 5, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const rootRound = structure.getRootRound();
    //     structureService.addQualifiers(rootRound, QualifyGroup.WINNERS, 3);

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(10);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 4);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(6);

    //     const secondRoundNumber = firstRoundNumber.getNext();
    //     const games2 = planningService.getGamesForRoundNumber(secondRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games2.length).to.equal(6);
    //     assertValidResourcesPerBatch(games2);
    //     secondRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games2, 4);
    //     });
    //     expect(games2.pop().getResourceBatch()).to.be.lessThan(7);
    // });

    // it('2 fields 2 sports, 4', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 4, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);

    //     planningService.create(firstRoundNumber);
    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     consoleGames(games1); console.log('');
    //     expect(games1.length).to.equal(6);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 3);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(7);

    //     firstRoundNumber.getValidPlanningConfig().setNrOfHeadtohead(2);
    //     planningService.create(firstRoundNumber);
    //     const games2 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     consoleGames(games2); console.log('');
    //     expect(games2.length).to.equal(12);
    //     assertValidResourcesPerBatch(games2);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games2, 6);
    //     });
    //     expect(games2.pop().getResourceBatch()).to.be.lessThan(7);
    // });

    // it('2 fields 2 sports, 44', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 8, 2);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);

    //     planningService.create(firstRoundNumber);
    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1); console.log('');
    //     expect(games1.length).to.equal(12);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 3);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(7);
    // });


    // it('3 fields 3 sports, 4', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);
    //     const sport3 = addSport(competition);
    //     const field3 = new Field(competition, 3); field3.setSport(sport3);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 4, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);

    //     planningService.create(firstRoundNumber);
    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(6);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 3);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(7);

    //     firstRoundNumber.getValidPlanningConfig().setNrOfHeadtohead(2);
    //     planningService.create(firstRoundNumber);
    //     const games2 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     consoleGames(games2);
    //     expect(games2.length).to.equal(12);
    //     assertValidResourcesPerBatch(games2);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games2, 6);
    //     });
    //     expect(games2.pop().getResourceBatch()).to.be.lessThan(7);

    //     firstRoundNumber.getValidPlanningConfig().setNrOfHeadtohead(3);
    //     planningService.create(firstRoundNumber);
    //     const games3 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games3);
    //     expect(games3.length).to.equal(18);
    //     assertValidResourcesPerBatch(games3);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games3, 9);
    //     });
    //     expect(games3.pop().getResourceBatch()).to.be.lessThan(13);
    // });

    // it('3 fields 3 sports, 44', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);
    //     const sport3 = addSport(competition);
    //     const field3 = new Field(competition, 3); field3.setSport(sport3);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 8, 2);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);

    //     planningService.create(firstRoundNumber);
    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(12);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 3);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(13);

    //     firstRoundNumber.getValidPlanningConfig().setNrOfHeadtohead(2);
    //     planningService.create(firstRoundNumber);
    //     const games2 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     consoleGames(games2);
    //     expect(games2.length).to.equal(24);
    //     assertValidResourcesPerBatch(games2);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games2, 6);
    //     });
    //     expect(games2.pop().getResourceBatch()).to.be.lessThan(13);
    // });

    // it('2 fields 2 sports, 5', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 5, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(10);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 4);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(6);
    // });

    // it('2 fields 2 sports, 55', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 10, 2);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(20);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 4);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(11);
    // });


    // it('3 fields 2 sports, 5', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);
    //     const field3 = new Field(competition, 3); field3.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 5, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(10);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 4);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(6);
    // });


    // // should not be possible, fields determine nrofsports
    // // it('2 sports(1 & 3 fields), 5', () => {
    // //     const competitionMapper = getMapper('competition');
    // //     const competition = competitionMapper.toObject(jsonCompetition);
    // //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    // //     const sport2 = addSport(competition);
    // //     const field2 = new Field(competition, 2); field2.setSport(sport2);
    // //     const field3 = new Field(competition, 3); field3.setSport(sport2);
    // //     const field4 = new Field(competition, 4); field4.setSport(sport2);

    // //     const structureService = new StructureService();
    // //     const structure = structureService.create(competition, 5, 1);
    // //     const firstRoundNumber = structure.getFirstRoundNumber();

    // //     const planningService = new PlanningService(competition);
    // //     planningService.create(firstRoundNumber);

    // //     const games = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    // //     // consoleGames(games1);
    // //     expect(games.length).to.equal(10);
    // //     assertValidResourcesPerBatch(games);
    // //     firstRoundNumber.getPlaces().forEach(place => {
    // //         this.assertValidGamesParticipations(place, games, 4);
    // //     });
    // //     expect(games.pop().getResourceBatch()).to.be.lessThan(6);
    // // });

    // it('2 fields check games in row, 6', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const field2 = new Field(competition, 2); field2.setSport(competition.getFirstSportConfig().getSport());

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 6, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games);
    //     expect(games.length).to.equal(15);
    //     assertValidResourcesPerBatch(games);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games, 5);
    //         this.assertGamesInRow(place, games, 3);
    //     });
    //     expect(games.pop().getResourceBatch()).to.be.lessThan(9);
    // });

    // it('check datetime, 5', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 5, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(10);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 4);
    //     });

    //     const lastGame = games1.pop();
    //     expect(lastGame.getResourceBatch()).to.equal(5);

    //     const endDateTime = planningService.calculateEndDateTime(firstRoundNumber);
    //     const nrOfMinutes = firstRoundNumber.getValidPlanningConfig().getMaximalNrOfMinutesPerGame();
    //     const lastGameStartDateTime = lastGame.getStartDateTime();
    //     lastGameStartDateTime.setMinutes(lastGameStartDateTime.getMinutes() + nrOfMinutes);
    //     expect(lastGameStartDateTime.getTime()).to.equal(endDateTime.getTime());
    // });


    // /**
    //  * time disabled
    //  */
    // it('time disabled 4', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const field2 = new Field(competition, 2); field2.setSport(competition.getFirstSportConfig().getSport());

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 4, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();
    //     firstRoundNumber.getPlanningConfig().setEnableTime(false);

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);
    //     planningService.reschedule(firstRoundNumber);

    //     const games = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games);
    //     expect(games.length).to.equal(6);

    //     assertValidResourcesPerBatch(firstRoundNumber.getGames());
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, firstRoundNumber.getGames(), 3);
    //     });
    // });

    // /**
    //  * time disabled
    //  */
    // it('second round losers before winners, 44', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 8, 2);
    //     const firstRoundNumber = structure.getFirstRoundNumber();
    //     const rootRound = structure.getRootRound();

    //     structureService.addQualifiers(rootRound, QualifyGroup.WINNERS, 2);
    //     structureService.addQualifiers(rootRound, QualifyGroup.LOSERS, 2);


    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);
    //     planningService.reschedule(firstRoundNumber);

    //     const secondRoundNumber = firstRoundNumber.getNext();
    //     planningService.reschedule(secondRoundNumber);
    //     const games = planningService.getGamesForRoundNumber(secondRoundNumber, Game.ORDER_RESOURCEBATCH);

    //     expect(games.length).to.equal(2);
    //     // consoleGames(games);
    //     expect(games[0].getRound().getParentQualifyGroup().getWinnersOrLosers()).to.equal(QualifyGroup.LOSERS);

    //     assertValidResourcesPerBatch(secondRoundNumber.getGames());
    //     secondRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, secondRoundNumber.getGames(), 1);
    //     });
    // });



    // it('recursive', () => {
    //     const numbers = [1, 2, 3, 4, 5, 6];
    //     const nrOfItemsPerBatch = 3;

    //     const itemSuccess = (newNumber: number): boolean => {
    //         return (newNumber % 2) === 1;
    //     };
    //     const endSuccess = (batch: number[]): boolean => {
    //         if (nrOfItemsPerBatch < batch.length) {
    //             return false;
    //         }
    //         let sum = 0;
    //         batch.forEach(number => sum += number);
    //         return sum === 9;
    //     };

    //     const showC = (list: number[], batch: number[] = []): boolean => {
    //         if (endSuccess(batch)) {
    //             console.log(batch);
    //             return true;
    //         }
    //         if (list.length + batch.length < nrOfItemsPerBatch) {
    //             return false;
    //         }
    //         const numberToTry = list.shift();
    //         if (itemSuccess(numberToTry)) {
    //             batch.push(numberToTry);
    //             if (showC(list.slice(), batch) === true) {
    //                 return true;
    //             }
    //             batch.pop();
    //             return showC(list, batch);

    //         }
    //         return showC(list, batch);
    //     };

    //     if (!showC(numbers)) {
    //         console.log('no combinations found');
    //     };
    // });
}