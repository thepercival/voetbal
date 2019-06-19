-- draaien voor doctrine-update
ALTER TABLE rounds MODIFY numberid INT NOT NULL;

-- draaien na doctrine-update
update rounds set winnersOrLosers = 3 where winnersOrLosers = 2;

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

-- all competitors without places
delete 	c
from 	competitors c
join associations a on a.id = c.associationid
left join pouleplaces p on p.competitorid = c.id
where 	a.id in ( select id from associations where ( select count(*) from leagues where associationid = associations.id ) = 0 )
and 		p.id is null;

delete from associations where ( select count(*) from leagues where associationid = associations.id ) = 0 and ( select count(*) from competitors where associationid = associations.id ) = 0;

insert into sports( name, scoreUnitName, teamup ) (	select distinct sportDep, 'punten', true from leagues );

update sports set teamup = false where name IN ('voetbal', 'volleybal', 'hockey', 'korfbal');

update associations set sportid = ( select s.id from sports s join leagues l on l.sportDep = s.name where l.associationid = associations.id limit 1 );

insert into countconfigs( qualifyRule, winPoints, drawPoints, winPointsExt, drawPointsExt, pointsCalculation, sportid )
(
	select	1,
					CASE name WHEN 'schaken' THEN 1 ELSE 3 END,
					CASE name WHEN 'schaken' THEN 0.5 ELSE 1 END,
					CASE name WHEN 'schaken' THEN 1 ELSE 2 END,
					CASE name WHEN 'schaken' THEN 0.5 ELSE 1 END,
					0,
					id
	from 	sports
);

let unitName = 'punten';
let parentUnitName;
if (sport === SportConfig.Darts) {
	unitName = 'legs';
	parentUnitName = 'sets';
}
else if (sport === SportConfig.Tennis) {
	unitName = 'games';
	parentUnitName = 'sets';
}
else if (sport === SportConfig.Squash || sport === SportConfig.TableTennis || sport === SportConfig.Volleyball || sport === SportConfig.Badminton) {
	parentUnitName = 'sets';
}
else if (sport === SportConfig.Football || sport === SportConfig.Hockey) {
	unitName = 'goals';
}
/** @type {?} */
let parent;
if (parentUnitName !== undefined) {
	parent = this.createScoreConfigFromRoundHelper(config, parentUnitName, RoundNumberConfigScore.UPWARDS, 0, undefined);
}
	return this.createScoreConfigFromRoundHelper(config, unitName, RoundNumberConfigScore.UPWARDS, 0, parent);
}





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


