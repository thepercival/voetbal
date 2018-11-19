-- execute script after checkout

insert into roundnumbers( competitionid, number, configid )
(
 select r.competitionid, r.number, rc.id from roundconfigs rc join rounds r on r.id = rc.roundid
 where not exists( select * from rounds rsub where rsub.competitionid = r.competitionid and rsub.number = r.number and rsub.id < r.id )
);

update rounds set  numberid = ( select id from roundnumbers rn where rn.competitionid = rounds.competitionid and rn.number = rounds.number );

delete from roundconfigs where not exists( select * from roundnumbers where configid = roundconfigs.id );