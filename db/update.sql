-- draaien voor doctrine-update
ALTER TABLE rounds MODIFY numberid INT NOT NULL;

-- draaien na doctrine-update
update rounds set winnersOrLosers = 3 where winnersOrLosers = 2;

-- add qualifyGroups
insert into qualifyGroups( roundid, winnersOrLosers, number, nrOfHorizontalPoules, childRoundId )
(
	select 	parentrounds.id, childrounds.winnersOrLosers, 1,
			ceil(
				( select count(*) from pouleplaces where pouleid in ( select id from poules where roundid = parentrounds.id ) ) / ( select count(*) from poules where roundid = parentrounds.id )
			) as nrOfHorizontalPoules,
			childrounds.id
	from 	rounds as childrounds
			join rounds as parentrounds on childrounds.parentid = parentrounds.id
);

-- add rounds and qualifyGroups and update rounds.parentQualifyGroup for (parent)rounds with QualifyOrder = 2


-- eerst qualifyOrder eruit en vervangen door
--
-- QualifyGroup( round, winnersOrLosers, number, nrOfHorizontalPoules )
--
-- winnersOrLosers, number komt overeen met children
--
--
--
--
-- vervang van round
-- 1 winnersOrLosers(alle qualifyrules moeten van 1 van beide zijn),
-- 2 parentId(niet meer van toepassing, meerdere parents mogelijk)
-- 3 qualifyOrder

-- bepaald gedrag bepaald hoe de qualifylines worden opgeslagen
-- gedrag kan zijn :
--		volledig handmatig(zou aparte view moeten hebben)
--		met winnaars en verliezers-groepen(groepen kunnen worden samengesteld uit

-- if input method is manual than
-- QualifyLines
-- id fromPoulePlaceId toPoulePlace winnersLosers
-- combinatie fromPoulePlaceId toPoulePlace is uniek

