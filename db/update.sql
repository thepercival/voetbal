-- PRE PRE PRE doctrine-update =============================================================


-- POST POST POST doctrine-update ===========================================================

delete  r.*
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
