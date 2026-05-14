-- 1. 创建数据库
CREATE DATABASE teaching
ON PRIMARY (
    NAME = 'teaching_data',
    FILENAME = 'D:\teaching_data.mdf',
    SIZE = 10MB,
    FILEGROWTH = 10%
);
GO

USE teaching;
GO

-- 2. 创建核心表结构 (示例：student, course, score)
CREATE TABLE student (
    sno CHAR(10) PRIMARY KEY,
    sname NVARCHAR(20) NOT NULL,
    ssex NCHAR(1),
    sbirthday DATE,
    classno CHAR(10),
    entrance_score INT
);

CREATE TABLE course (
    cno CHAR(10) PRIMARY KEY,
    cname NVARCHAR(50),
    credit FLOAT
);

CREATE TABLE score (
    sno CHAR(10) REFERENCES student(sno),
    cno CHAR(10) REFERENCES course(cno),
    grade FLOAT,
    PRIMARY KEY (sno, cno)
);

-- 3. 插入、修改、删除记录
INSERT INTO student VALUES ('2023001', '张三', '男', '2004-01-01', 'C01', 580);
UPDATE student SET sname = '张小三' WHERE sno = '2023001';
DELETE FROM student WHERE sno = '2023001';  