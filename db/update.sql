-- execute script after checkout 61d6520 on mon-19-nov 09:38

insert into roundnumbers( competitionid, number, configid )
(
 select r.competitionid, rc.id from roundconfigs rc join rounds r on r.id = rc.roundid
 where not exists( select * from rounds rsub where rsub.competitionid = r.competitionid and rsub.number = r.number and rsub.id < r.id )
);

update rounds set  numberid = ( select id from roundnumbers rn where rn.competitionid = rounds.competitionid and rn.number = rounds.number );

delete from roundconfigs where not exists( select * from roundnumbers where configid = roundconfigs.id );

update roundnumbers as rn inner join ( select * from roundnumbers ) as rn2 on rn2.competitionid = rn.competitionid and rn2.number = (rn.number - 1)  set rn.previousid = rn2.id

select r.competitionid, rc.id from roundconfigs rc join rounds r on r.id = rc.roundid
where not exists( select * from rounds rsub where rsub.competitionid = r.competitionid and rsub.number = r.number and rsub.id < r.id )


select * from rounds