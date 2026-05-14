package com.boda.xy;
import java.time.LocalDate;
import java.util.Arrays;
public class EmployeeTest {
	public static void main(String[] args) {
		Employee[] empArray = new Employee[] {
			new Employee(105, "张三",LocalDate.of(1980,12, 15), 6000),
			new Employee(102, "李四",LocalDate.of(2002,8, 28), 4000),
			new Employee(108, "王五",LocalDate.of(2001,10, 10), 4800)
		};
        Arrays.sort(empArray);
        
        for(var emp: empArray) {
        	System.out.println(emp.getId() + "  " + emp.getName() + " " 
                        + emp.getBirthday() + "  " + emp.getSalary() );
        }
//        System.out.println();
//        Arrays.sort(empArray,new AgeComparator());
//        for(var emp: empArray) {
//        	System.out.println(emp.getId() + "  " + emp.getName() + " " 
//                        + emp.getBirthday() + "  " + emp.getSalary() );
//        }
	}
}
