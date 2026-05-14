CREATE DATABASE StuManagement;
USE StuManagement;
CREATE TABLE student (
    sno VARCHAR(20) PRIMARY KEY,
    sname VARCHAR(20),
    ssex VARCHAR(2),
    sage INT
);
INSERT INTO student VALUES ('1001', '张三', '男', 20);