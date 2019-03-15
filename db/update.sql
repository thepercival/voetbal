-- execute script after checkout 61d6520 on mon-19-nov 09:38
-- alter table rounds drop foreign key FK_3A7FD554ECE66465;
-- drop index IDX_3A7FD554ECE66465 on rounds;
-- alter table rounds drop numberid;
-- drop table roundnumbers;


RENAME TABLE teams TO competitors;

RENAME TABLE externalteams TO externalcompetitors;

alter table pouleplaces change teamid competitorid int;

update competitions set ruleset = 1; -- QualifyRule::SOCCERWORLDCUP

-- maak daarna ruleSet -> nullable = false




