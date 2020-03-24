-- PRE PRE PRE doctrine-update =============================================================
alter table roundnumbers drop foreign key FK_4A1A14D69762B879;
update sports set team = true, customId = 14 where name = 'ijshockey';

-- POST POST POST doctrine-update ===========================================================
update tournaments set breakStartDateTime = null where breakStartDateTime is not null and breakDuration = 0;
update tournaments set breakEndDateTime = DATE_ADD(breakStartDateTime, INTERVAL breakDuration MINUTE) where breakStartDateTime is not null;

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
