-- draaien voor doctrine-update
ALTER TABLE rounds MODIFY numberid INT NOT NULL;

-- draaien na doctrine-update
update rounds set winnersOrLosers = 3 where winnersOrLosers = 2;

-- update configs
update roundconfigs set scoreid = ( select id from roundscoreconfigs where parentid is null and roundconfigid = roundconfigs.id );
update roundscoreconfigs set roundconfigid = null;



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


