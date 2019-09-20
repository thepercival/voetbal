-- draaien voor doctrine-update
-- ALTER TABLE rounds MODIFY numberid INT NOT NULL;

-- draaien na doctrine-update
update rounds set winnersOrLosers = 3 where winnersOrLosers = 2;

update gamescores set phase = 1 where gameid in ( select id from games where scoresmoment = 2 and id = gamescores.gameid );
update gamescores set phase = 2 where gameid in ( select id from games where scoresmoment = 4 and id = gamescores.gameid );

update planningconfigs set minutesPerGameExt = 0 where hasExtension = false and minutesPerGameExt > 0;

update referees set rank = ( select count(*) from referees rsub where rsub.competitionid = referees.competitionid and rsub.id < referees.id ) + 1;

-- all competitors without places
delete 	c
from 	competitors c
join associations a on a.id = c.associationid
left join pouleplaces p on p.competitorid = c.id
where 	a.id in ( select id from associations where ( select count(*) from leagues where associationid = associations.id ) = 0 )
and 		p.id is null;

delete from associations where ( select count(*) from leagues where associationid = associations.id ) = 0 and ( select count(*) from competitors where associationid = associations.id ) = 0;

insert into sports( name, team ) (	select distinct sportDep, false from leagues );

update sports set customId = 1 where name = 'badminton';
update sports set customId = 2, team = true where name = 'basketbal';
update sports set customId = 3 where name = 'darten';
update sports set customId = 4 where name = 'e-sporten';
update sports set customId = 5, team = true where name = 'hockey';
update sports set customId = 6, team = true where name = 'korfbal';
update sports set customId = 7 where name = 'schaken';
update sports set customId = 8 where name = 'squash';
update sports set customId = 9 where name = 'tafeltennis';
update sports set customId = 10 where name = 'tennis';
update sports set customId = 11, team = true where name = 'voetbal';
update sports set customId = 12, team = true where name = 'volleybal';

-- add sportconfigs for competition
insert into sportconfigs( competitionid, sportid, winPoints, drawPoints, winPointsExt, drawPointsExt, pointsCalculation, nrOfGamePlaces )
    (
        select 	c.id, s.id, rc.winPoints, rc.drawPoints, rc.winPointsExt, rc.drawPointsExt, rc.pointsCalculation, 2
        from 	competitions c
                    join leagues l on l.id = c.leagueid
                    join sports s on s.name = l.sportDep
                    join roundnumbers rn on c.id = rn.competitionid and rn.number = 1
                    join roundconfigs rc on rc.id = rn.configid
    );

-- add sportscoreconfigs for roundnumbers
insert into sportscoreconfigs ( roundnumberid, sportid, direction, maximum, parentid, iddep )
    (
        select 	rn.id, s.id, rsc.direction, rsc.direction, null, rsc.id
        from    roundscoreconfigs rsc
                    join roundconfigs rc on rc.id = rsc.roundconfigid
                    join roundnumbers rn on rc.id = rn.configid
                    join competitions c on c.id = rn.competitionid
                    join leagues l on l.id = c.leagueid
                    join sports s on s.name = l.sportDep
    );
update 	sportscoreconfigs ssc
        join 	roundscoreconfigs rsc on ssc.iddep = rsc.id
        join 	sportscoreconfigs scp on scp.iddep = rsc.parentid
set 	ssc.parentid = scp.id
where	rsc.parentid is not null;

-- add planningconfigs to roundnumber
insert into planningconfigs( hasExtension,minutesPerGameExt,enableTime,minutesPerGame,minutesInBetween,minutesBetweenGames,teamup,selfReferee, nrOfHeadtohead, rniddep )
    (
        select rc.hasExtension, rc.minutesPerGameExt, rc.enableTime, rc.minutesPerGame, rc.minutesInBetween, rc.minutesBetweenGames, rc.teamup, rc.selfReferee, rc.nrOfHeadtoheadMatches, rn.id
        from roundnumbers rn join roundconfigs rc on rn.configid = rc.id
    );
update roundnumbers set planningconfigid = ( select id from planningconfigs where rniddep = roundnumbers.id );

-- add sportplanningconfigs for roundnumbers
insert into sportplanningconfigs ( roundnumberid, sportid, minNrOfGames )
    (
        select  rn.id, s.id, 1
        from    roundnumbers rn join roundconfigs rc on rn.configid = rc.id
                                join competitions c on c.id = rn.competitionid
                                join leagues l on l.id = c.leagueid
                                join sports s on s.name = l.sportDep
    );

-- add sport to field
update fields f join sportconfigs sc on sc.competitionid = f.competitionid set f.sportid = sc.sportid;

-- remove orphan competitions
delete c from competitions c where c.id in (897, 898 ) and not exists ( select * from roundnumbers where competitionid = c.id );

-- add at least one field to a competition, @TODO do a recalc of the planning!!
insert into fields( competitionid, number, name, sportid )
  (
    select id, 1, '1', ( select sp.sportid from sportconfigs sp where sp.competitionid = competitions.id )
    from competitions where not exists( select * from fields where competitionid = competitions.id )
  );

-- @TODO update old structures!!

-- add qualifyGroups
-- insert into qualifygroups( roundid, winnersOrLosers, number, childRoundId ) -- nrOfHorizontalPoules
-- (
--	select 	parentrounds.id, childrounds.winnersOrLosers, 1,
--			-- ceil(
--  -- 	( select count(*) from pouleplaces where pouleid in ( select id from poules where roundid = parentrounds.id ) ) / ( select count(*) from poules where roundid = parentrounds.id )
--          -- ) as nrOfHorizontalPoules,
		--	childrounds.id
-- from 	rounds as childrounds
--		join rounds as parentrounds on childrounds.parentid = parentrounds.id
-- );

-- add rounds and qualifyGroups and update rounds.parentQualifyGroup for (parent)rounds with QualifyOrder = 2




