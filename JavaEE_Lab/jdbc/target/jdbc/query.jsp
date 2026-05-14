<%@ page import="java.sql.Connection,java.sql.ResultSet,java.sql.Statement,com.jdbc.util.DBUtil" %>
<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>学生列表</title>
</head>
<body>
    <h2>学生列表</h2>
    <%
        try (Connection conn = DBUtil.getConnection();
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery("SELECT sno, sname, ssex, sage FROM student")) {
            while (rs.next()) {
    %>
        <p>
            学号：<%= rs.getString("sno") %>
            姓名：<%= rs.getString("sname") %>
            性别：<%= rs.getString("ssex") %>
            年龄：<%= rs.getInt("sage") %>
        </p>
    <%
            }
        } catch (Exception e) {
    %>
        <p>查询失败：<%= e.getMessage() %></p>
        <p>请检查数据库是否已按 tab.sql 初始化，以及连接参数是否正确。</p>
    <%
        }
    %>
    <a href="index.jsp">返回首页</a>
</body>
</html>