-- PRE PRE PRE doctrine-update =============================================================
alter table roundnumbers drop foreign key FK_4A1A14D69762B879;
update sports set team = true, customId = 14 where name = 'ijshockey';

-- POST POST POST doctrine-update ===========================================================
update tournaments set breakStartDateTime = null where breakStartDateTime is not null and breakDuration = 0;
update tournaments set breakEndDateTime = DATE_ADD(breakStartDateTime, INTERVAL breakDuration MINUTE) where breakStartDateTime is not null;
update planningconfigs set extension = ( minutesPerGameExt > 0 );

delete	r.*
from 	rounds r
            join roundnumbers rn on rn.id = r.numberid
            join competitions c on c.id = rn.competitionid
            join leagues l on l.id = c.leagueid
            join tournaments t on t.competitionid = c.id
where 	not exists ( select * from games g join poules p on p.id = g.pouleid where p.roundid = r.id )
and	    not exists( select * from qualifygroups where roundid = r.id );

delete from qualifygroups where not exists ( select * from rounds where parentQualifyId = qualifygroups.id );

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
