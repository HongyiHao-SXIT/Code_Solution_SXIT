package com.jdbc.util;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public final class DBUtil {
    private static final String URL = System.getProperty(
            "jdbc.url",
            "jdbc:mysql://localhost:3306/StuManagement?useUnicode=true&characterEncoding=UTF-8&serverTimezone=Asia/Shanghai");
    private static final String USER = System.getProperty("jdbc.user", "root");
    private static final String PASSWORD = System.getProperty("jdbc.password", "");

    private DBUtil() {
    }

    public static Connection getConnection() throws SQLException, ClassNotFoundException {
        Class.forName("com.mysql.cj.jdbc.Driver");
        return DriverManager.getConnection(URL, USER, PASSWORD);
    }
}