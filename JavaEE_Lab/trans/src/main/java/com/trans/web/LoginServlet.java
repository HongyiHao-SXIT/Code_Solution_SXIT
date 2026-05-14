package com.trans.web;

import java.io.IOException;

import javax.servlet.RequestDispatcher;
import javax.servlet.ServletException;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

public class LoginServlet extends HttpServlet {
    private static final long serialVersionUID = 1L;

    @Override
    protected void doPost(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        request.setCharacterEncoding("UTF-8");
        String name = request.getParameter("username");

        if ("admin".equals(name)) {
            request.setAttribute("msg", "欢迎管理员！");
            RequestDispatcher dispatcher = request.getRequestDispatcher("/welcome.jsp");
            dispatcher.forward(request, response);
            return;
        }

        response.sendRedirect(request.getContextPath() + "/error.jsp");
    }
}