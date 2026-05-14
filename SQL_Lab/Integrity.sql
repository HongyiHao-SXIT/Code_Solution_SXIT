-- 1. 设置外键和唯一约束
ALTER TABLE score ADD CONSTRAINT FK_score_student FOREIGN KEY (sno) REFERENCES student(sno);
ALTER TABLE student ADD CONSTRAINT UQ_sname UNIQUE (sname);

-- 2. 设置CHECK约束 (成绩在0-100之间)
ALTER TABLE score ADD CONSTRAINT CK_grade CHECK (grade >= 0 AND grade <= 100);

-- 3. 设置默认值 (性别默认为'男')
ALTER TABLE student ADD CONSTRAINT DF_ssex DEFAULT '男' FOR ssex;