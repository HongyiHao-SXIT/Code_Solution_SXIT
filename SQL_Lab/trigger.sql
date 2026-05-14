-- (1) 创建存储过程：根据学号查询平均分
CREATE PROCEDURE get_avg_score @student_no CHAR(10)
AS
BEGIN
    SELECT AVG(grade) FROM score WHERE sno = @student_no;
END;
GO

-- (2) 创建触发器：当删除学生记录时，同步删除其成绩记录
CREATE TRIGGER trg_delete_student
ON student
FOR DELETE
AS
BEGIN
    DELETE FROM score WHERE sno IN (SELECT sno FROM deleted);
END;