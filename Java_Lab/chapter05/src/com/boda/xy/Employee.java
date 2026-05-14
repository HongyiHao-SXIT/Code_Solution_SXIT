package com.boda.xy;
public class Employee extends Person{
public double salary;   // 表示员工工资
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
        // 这里计算员工的工资
        double salary =  hours * rate;
        return this.salary + salary;
    }
	@Override
	public String toString() {
		return "Employee [salary=" + salary + ", name=" + name + ", age=" + age + "]";
	}

//	public void sayHello(){
//        System.out.println("Hello,My name is " + name);
//        System.out.println("I am an employee.");
//}   
}

