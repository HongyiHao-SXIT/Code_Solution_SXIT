<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<jsp:useBean id="stu" class="com.student.bean.Student" scope="session" />
<jsp:setProperty name="stu" property="*" />
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>保存成功</title>
</head>
<body>
	<h2>学生信息已保存到 Session</h2>
	<p>姓名：${stu.name}</p>
	<p>年龄：${stu.age}</p>
	<a href="display.jsp">查看 Session 中的学生信息</a>
</body>
</html>