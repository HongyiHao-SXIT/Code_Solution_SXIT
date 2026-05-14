package com.boda.xy;
public class CastDemo{
    public static void main(String[] args){
       var emp = new Employee("张明月",30,5000);
       System.out.println(emp);
       Person p = emp;            // 自动类型转换
       System.out.println(p);
       p.sayHello();
       System.out.println(p.getClass().getName());
       //System.out.println(p.computeSalary(5,50));  编译错误
       emp = (Employee)p;        // 强制类型转换
       System.out.println(emp.computeSalary(5,50));
    }
}

