-- PRE PRE PRE doctrine-update =============================================================
alter table pouleplaces rename places;

alter table gamepouleplaces rename gameplaces;

ALTER TABLE games
    CHANGE pouleplacerefereeid placerefereeid INT;

ALTER TABLE gameplaces
    CHANGE pouleplaceid placeid INT;

-- POST POST POST doctrine-update ===========================================================

update fields
set sportConfigId = (select id
                     from sportconfigs
                     where competitionId = fields.CompetitionId
                       and sportId = fields.sportid);

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
