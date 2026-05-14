-- (1) 查询计算机系学生的学号、姓名、性别
SELECT sno, sname, ssex FROM student WHERE classno LIKE 'CS%';

-- (2) 查询选修了c05127课程的学生姓名及成绩
SELECT s.sname, sc.grade 
FROM student s JOIN score sc ON s.sno = sc.sno 
WHERE sc.cno = 'c05127';

-- (3) 统计每门课的平均成绩
SELECT cno, AVG(grade) AS avg_grade FROM score GROUP BY cno;