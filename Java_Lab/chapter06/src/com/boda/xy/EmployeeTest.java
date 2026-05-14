package com.boda.xy;
public class EmployeeTest{
public static void main(String[] args){
    var p = new Person();
    var emp = (Employee)p;
    System.out.println(emp);
    
	
//	var emp = new Employee("张明月",30,5000);
//       System.out.println("姓名 = " + emp.name);
//	    System.out.println("年龄 = " + emp.age);
//	    emp.sayHello();       // 调用从父类继承的方法
//       System.out.println(emp.computeSalary(10, 50.0)); // 调用子类定义的方法
}
}

