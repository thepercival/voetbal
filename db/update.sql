-- draaien voor doctrine-update
ALTER TABLE rounds MODIFY numberid INT NOT NULL;

-- draaien na doctrine-update
update rounds set winnersOrLosers = 3 where winnersOrLosers = 2;

-- all competitors without places
delete 	c
from 	competitors c
join associations a on a.id = c.associationid
left join pouleplaces p on p.competitorid = c.id
where 	a.id in ( select id from associations where ( select count(*) from leagues where associationid = associations.id ) = 0 )
and 		p.id is null;

delete from associations where ( select count(*) from leagues where associationid = associations.id ) = 0 and ( select count(*) from competitors where associationid = associations.id ) = 0;

insert into sports( name, scoreUnitName, teamup ) (	select distinct sportDep, 'punten', true from leagues );

update sports set customId = 1, scoreUnitName = 'sets' where name = 'badminton';
update sports set customId = 2, teamup = false where name = 'basketbal';
update sports set customId = 3, scoreUnitName = 'sets', scoreSubUnitName = 'legs' where name = 'darten';
update sports set customId = 4 where name = 'e-sporten';
update sports set customId = 5, scoreUnitName = 'goals', teamup = false where name = 'hockey';
update sports set customId = 6, teamup = false where name = 'korfbal';
update sports set customId = 7 where name = 'schaken';
update sports set customId = 8, scoreUnitName = 'sets' where name = 'squash';
update sports set customId = 9, scoreUnitName = 'sets' where name = 'tafeltennis';
update sports set customId = 10, scoreUnitName = 'sets', scoreSubUnitName = 'games' where name = 'tennis';
update sports set customId = 11, scoreUnitName = 'goals', teamup = false where name = 'voetbal';
update sports set customId = 12, scoreUnitName = 'sets', teamup = false where name = 'volleybal';

-- add countconfigs and countscoreconfigs for sports
insert into countconfigs( qualifyRule, winPoints, drawPoints, winPointsExt, drawPointsExt, pointsCalculation, sportid )
(
	select	1,
					CASE name WHEN 'schaken' THEN 1 ELSE 3 END,
					CASE name WHEN 'schaken' THEN 0.5 ELSE 1 END,
					CASE name WHEN 'schaken' THEN 1 ELSE 2 END,
					CASE name WHEN 'schaken' THEN 0.5 ELSE 1 END,
					0,
					id
	from sports
);

-- update sports
update  sports s join countconfigs cc on cc.sportid = s.id
set 		s.countconfigid = cc.id;

-- add countscoreconfigs
-- select * from countconfigs cc join sports s on s.id = cc.sportid left join countscoreconfigs csc on cc.scoreid = csc.id where cc.roundnumberid is null and s.name = 'darten';
-- select * from countscoreconfigs c where parentid = ( select cc.scoreid from countconfigs cc join sports s on s.id = cc.sportid left join countscoreconfigs csc on cc.scoreid = csc.id where cc.roundnumberid is null and s.name = 'darten' );
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'badminton'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'badminton';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'basketbal'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'basketbal';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'darten'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'darten';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select cc.scoreid, 1, 0 from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'darten'
	);
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'e-sporten'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'e-sporten';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'hockey'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'hockey';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'korfbal'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'korfbal';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'schaken'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'schaken';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'squash'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'squash';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'tafeltennis'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'tafeltennis';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'tennis'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'tennis';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select cc.scoreid, 1, 0 from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'tennis'
	);
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'voetbal'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'voetbal';
insert into countscoreconfigs( parentid, direction, maximum )
	(
		select null, 1, 0  from countconfigs cc join sports s on s.id = cc.sportid where cc.roundnumberid is null and s.name = 'volleybal'
	);
update countconfigs cc join sports s on s.id = cc.sportid set cc.scoreid = LAST_INSERT_ID() where cc.roundnumberid is null and s.name = 'volleybal';

-- move league.sport to association
update associations set sportid = ( select s.id from sports s join leagues l on l.sportDep = s.name where l.associationid = associations.id limit 1 );

-- add countconfigs and countscoreconfigs for roundnumbers
insert into countconfigs( roundnumberid, qualifyRule, winPoints, drawPoints, winPointsExt, drawPointsExt, pointsCalculation )
(
	select rn.id, rc.qualifyRule, rc.winPoints, rc.drawPoints, rc.winPointsExt, rc.drawPointsExt, rc.pointsCalculation from roundnumbers rn join roundconfigs rc on rn.configid = rc.id
);
update 	countscoreconfigs sc
				join 	roundscoreconfigs rsc on sc.iddep = rsc.id
				join 	countscoreconfigs scp on scp.iddep = rsc.parentid
set 		sc.parentid = scp.id
where		rsc.parentid is not null;
update 	countconfigs cc
				join	roundnumbers rn on cc.roundnumberid = rn.id
				join 	roundconfigs rc on rn.configid = rc.id
set cc.scoreid = ( select csc.id from countscoreconfigs csc where csc.roundconfigiddep = rc.id and csc.parentid is null );

-- @TODO!!








-- add qualifyGroups
insert into qualifygroups( roundid, winnersOrLosers, number, childRoundId ) -- nrOfHorizontalPoules
(
	select 	parentrounds.id, childrounds.winnersOrLosers, 1,
			-- ceil(
  -- 	( select count(*) from pouleplaces where pouleid in ( select id from poules where roundid = parentrounds.id ) ) / ( select count(*) from poules where roundid = parentrounds.id )
          -- ) as nrOfHorizontalPoules,
			childrounds.id
	from 	rounds as childrounds
			join rounds as parentrounds on childrounds.parentid = parentrounds.id
);

-- add rounds and qualifyGroups and update rounds.parentQualifyGroup for (parent)rounds with QualifyOrder = 2


-- eerst qualifyOrder eruit en vervangen door
--
-- QualifyGroup( round, winnersOrLosers, number )
--
-- winnersOrLosers, number komt overeen met children


