-- (4) 查询没有被选修的课程 (子查询)
SELECT cno, cname FROM course 
WHERE cno NOT IN (SELECT DISTINCT cno FROM score);

-- (5) 查询入学成绩最高的学生 (子查询)
SELECT sno, sname, entrance_score FROM student 
WHERE entrance_score = (SELECT MAX(entrance_score) FROM student);

-- (8) 使用游标输出成绩
DECLARE score_cursor CURSOR FOR 
SELECT s.sname, c.cname, sc.grade 
FROM student s JOIN score sc ON s.sno = sc.sno JOIN course c ON sc.cno = c.cno;

OPEN score_cursor;
FETCH NEXT FROM score_cursor;
-- ... 循环FETCH并处理 ...
CLOSE score_cursor;
DEALLOCATE score_cursor;