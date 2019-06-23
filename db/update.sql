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

insert into sports( name, nrOfGameCompetitors, teamup ) (	select distinct sportDep, 2, true from leagues );

update sports set customId = 1 where name = 'badminton';
update sports set customId = 2, teamup = false where name = 'basketbal';
update sports set customId = 3 where name = 'darten';
update sports set customId = 4 where name = 'e-sporten';
update sports set customId = 5, teamup = false where name = 'hockey';
update sports set customId = 6, teamup = false where name = 'korfbal';
update sports set customId = 7 where name = 'schaken';
update sports set customId = 8 where name = 'squash';
update sports set customId = 9 where name = 'tafeltennis';
update sports set customId = 10 where name = 'tennis';
update sports set customId = 11, teamup = false where name = 'voetbal';
update sports set customId = 12, teamup = false where name = 'volleybal';

-- move league.sport to association
update associations set sportid = ( select s.id from sports s join leagues l on l.sportDep = s.name where l.associationid = associations.id limit 1 );

-- add countconfigs and countscoreconfigs for roundnumbers
insert into countconfigs( roundnumberid, qualifyRule, winPoints, drawPoints, winPointsExt, drawPointsExt, pointsCalculation, sportid )
	(
		select rn.id, rc.qualifyRule, rc.winPoints, rc.drawPoints, rc.winPointsExt, rc.drawPointsExt, rc.pointsCalculation, a.sportid
		from roundnumbers rn
					 join roundconfigs rc on rn.configid = rc.id
					 join competitions c on c.id = rn.competitionid
					 join leagues l on l.id = c.leagueid
					 join associations a on a.id = l.associationid
	);

insert into countscoreconfigs ( parentid, direction, maximum, iddep, roundconfigiddep )
	(
		select null, direction, maximum, id, roundconfigid from roundscoreconfigs
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

-- add sport to field
update fields f
  join competitions c on c.id = f.competitionid
  join roundnumbers rn on rn.competitionid = c.id and rn.number = 1
  join countconfigs cc on cc.roundnumberid = rn.id
set f.sportid = cc.sportid;

-- remove orphan competitions
delete c from competitions c where c.id in (897, 898 ) and not exists ( select * from roundnumbers where competitionid = c.id );

-- @TODO update old structures!!

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




