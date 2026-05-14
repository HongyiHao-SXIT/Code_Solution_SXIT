package com.boda.xy;
public class Employee extends Person{
public double salary;   
// 无参构造方法
public Employee(){
}
// 带一个参数构造方法
public Employee(double salary){
     this.salary = salary;
}
// 带3个参数构造方法
public Employee (String name,int age,double salary){
     super(name,age);
     this.salary = salary;
}
     public double computeSalary(int hours, double rate) {
         double salary =  hours * rate;
         return this.salary + salary;
     }
}
