-- PRE PRE PRE doctrine-update =============================================================

-- POST POST POST doctrine-update ===========================================================
update planninginputs
set selfReferee = 2
where selfReferee = 1
  and structureConfig not like '%,%';

update plannings
set validity = -1;

-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
