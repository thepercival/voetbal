-- next release remove round.qualifyOrder

-- draaien voor doctrine-update
-- alter table roundnumbers drop foreign key FK_4A1A14D69762B879;

-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
