-- execute script after checkout 61d6520 on mon-19-nov 09:38
-- alter table rounds drop foreign key FK_3A7FD554ECE66465;
-- drop index IDX_3A7FD554ECE66465 on rounds;
-- alter table rounds drop numberid;
-- drop table roundnumbers;

insert into roundnumbers( competitionid, number, configid )
 (
  select r.competitionid, ( SELECT CASE
                                    WHEN r7.id is not null THEN 7
                                    WHEN r6.id is not null THEN 6
                                    WHEN r5.id is not null THEN 5
                                    WHEN r4.id is not null THEN 4
                                    WHEN r3.id is not null THEN 3
                                    WHEN r2.id is not null THEN 2
                                    ELSE 1
                                    END AS depth
                            FROM rounds AS r1
                                  LEFT JOIN rounds AS r2 ON r1.parentid = r2.id
                                  LEFT JOIN rounds AS r3 ON r2.parentid = r3.id
                                  LEFT JOIN rounds AS r4 ON r3.parentid = r4.id
                                  LEFT JOIN rounds AS r5 ON r4.parentid = r5.id
                                  LEFT JOIN rounds AS r6 ON r5.parentid = r6.id
                                  LEFT JOIN rounds AS r7 ON r6.parentid = r7.id
                            WHERE r1.id = r.id
  ), rc.id from roundconfigs rc join rounds r on r.id = rc.roundid
  where not exists( select * from rounds rsub where rsub.competitionid = r.competitionid and
    ( SELECT CASE
              WHEN rsub7.id is not null THEN 7
              WHEN rsub6.id is not null THEN 6
              WHEN rsub5.id is not null THEN 5
              WHEN rsub4.id is not null THEN 4
              WHEN rsub3.id is not null THEN 3
              WHEN rsub2.id is not null THEN 2
              ELSE 1
              END AS depth
      FROM rounds AS rsub1
            LEFT JOIN rounds AS rsub2 ON rsub1.parentid = rsub2.id
            LEFT JOIN rounds AS rsub3 ON rsub2.parentid = rsub3.id
            LEFT JOIN rounds AS rsub4 ON rsub3.parentid = rsub4.id
            LEFT JOIN rounds AS rsub5 ON rsub4.parentid = rsub5.id
            LEFT JOIN rounds AS rsub6 ON rsub5.parentid = rsub6.id
            LEFT JOIN rounds AS rsub7 ON rsub6.parentid = rsub7.id
      WHERE rsub1.id = rsub.id
    )
    =
    ( SELECT CASE
              WHEN r7.id is not null THEN 7
              WHEN r6.id is not null THEN 6
              WHEN r5.id is not null THEN 5
              WHEN r4.id is not null THEN 4
              WHEN r3.id is not null THEN 3
              WHEN r2.id is not null THEN 2
              ELSE 1
              END AS depth
      FROM rounds AS r1
            LEFT JOIN rounds AS r2 ON r1.parentid = r2.id
            LEFT JOIN rounds AS r3 ON r2.parentid = r3.id
            LEFT JOIN rounds AS r4 ON r3.parentid = r4.id
            LEFT JOIN rounds AS r5 ON r4.parentid = r5.id
            LEFT JOIN rounds AS r6 ON r5.parentid = r6.id
            LEFT JOIN rounds AS r7 ON r6.parentid = r7.id
      WHERE r1.id = r.id
    )
                                                and rsub.id < r.id )
 );

update rounds LEFT JOIN rounds AS r2 ON rounds.parentid = r2.id
                                        LEFT JOIN rounds AS r3 ON r2.parentid = r3.id
                                        LEFT JOIN rounds AS r4 ON r3.parentid = r4.id
                                        LEFT JOIN rounds AS r5 ON r4.parentid = r5.id
                                        LEFT JOIN rounds AS r6 ON r5.parentid = r6.id
                                        LEFT JOIN rounds AS r7 ON r6.parentid = r7.id
set rounds.numberid = ( select id from roundnumbers rn where rn.competitionid = rounds.competitionid and rn.number =
 ( SELECT CASE
    WHEN r7.id is not null THEN 7
    WHEN r6.id is not null THEN 6
    WHEN r5.id is not null THEN 5
    WHEN r4.id is not null THEN 4
    WHEN r3.id is not null THEN 3
    WHEN r2.id is not null THEN 2
    ELSE 1
    END )
 );

delete from roundconfigs where not exists( select * from roundnumbers where configid = roundconfigs.id );

update roundnumbers as rn inner join ( select * from roundnumbers ) as rn2 on rn2.competitionid = rn.competitionid and rn2.number = (rn.number - 1)  set rn.previousid = rn2.id;

-- deze sql moet uitgevoerd kunnen worden, en dan zo blijven na het aanmaken, wijzigen van een nieuwe ronde
-- 2X ???
delete from rounds where ( select count(*) from pouleplaces pp join poules p on pp.pouleid = p.id where p.roundid = rounds.id ) = 1;

update seasons set enddatetime = DATE_ADD(enddatetime, INTERVAL 1 DAY);