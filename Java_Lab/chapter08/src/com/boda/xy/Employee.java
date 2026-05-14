package com.boda.xy;
import java.time.LocalDate;
public class Employee implements Comparable<Employee>{
	private int id;
	private String name;
	private LocalDate birthday;
	private double salary;
	
	public Employee(){
	}
	// 带3个参数构造方法
	public Employee (int id, String name,LocalDate birthday,double salary){
	     this.id = id;
	     this.name = name;
	     this.birthday = birthday;
	     this.salary = salary;
	}
	
	public int getId() {
		return id;
	}
	public void setId(int id) {
		this.id = id;
	}
	public LocalDate getBirthday() {
		return birthday;
	}
	public void setBirthday(LocalDate birthday) {
		this.birthday = birthday;
	}
	
	public String getName() {
		return name;
	}
	public double getSalary() {
		return salary;
	}
	@Override
	public int compareTo(Employee employee){
	    if(getId() > employee.getId())
	       return 1;
	    else if (getId() < employee.getId())
	       return -1;
	    else 
	       return 0;
	}	
}
