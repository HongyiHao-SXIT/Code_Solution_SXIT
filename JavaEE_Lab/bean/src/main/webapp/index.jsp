<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>学生信息录入</title>
</head>
<body>
	<h2>学生信息录入</h2>
	<form action="save.jsp" method="post">
		<p>
			<label>姓名：</label>
			<input type="text" name="name" required />
		</p>
		<p>
			<label>年龄：</label>
			<input type="number" name="age" min="1" required />
		</p>
		<button type="submit">保存到 Session</button>
	</form>
</body>
</html>
