-- (1) 创建非聚集非唯一索引
CREATE INDEX UC_classno ON student(classno);

-- (2) 创建非聚集唯一索引 (若存在先删除)
IF EXISTS (SELECT * FROM sys.indexes WHERE name = 'UQ_name')
    DROP INDEX teacher.UQ_name;
CREATE UNIQUE INDEX UQ_name ON teacher(tname);

-- (3) 创建视图：查询选修了'数据库'课程的学生信息
CREATE VIEW view_db_student AS
SELECT s.* FROM student s 
JOIN score sc ON s.sno = sc.sno 
JOIN course c ON sc.cno = c.cno
WHERE c.cname = '数据库';