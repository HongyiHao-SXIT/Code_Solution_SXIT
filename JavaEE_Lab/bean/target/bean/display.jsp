<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="com.student.bean.Student" %>
<%
    Student student = (Student) session.getAttribute("stu");
%>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Session 学生信息</title>
</head>
<body>
    <h2>Session 中的学生信息</h2>
    <% if (student == null) { %>
        <p>当前 Session 中没有学生信息，请先录入。</p>
    <% } else { %>
        <p>姓名：<%= student.getName() %></p>
        <p>年龄：<%= student.getAge() %></p>
    <% } %>
    <a href="index.jsp">返回录入页面</a>
</body>
</html>