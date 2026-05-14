package com.boda.xy;
import java.util.HashSet;
import java.util.Set;
import java.util.TreeSet;

public class StudentDemo{
	 public static void main(String[] args) {
		 //创建学生对象
		 Student stud1=new Student(190101,"王鹏",90,87,89);
		 Student stud2=new Student(190102,"张贺",67,75,83);
		 Student stud3=new Student(190201,"李某",86,71,77);
		 Student stud4=new Student(190211,"李丽",93,82,90);
		 Student stud5=new Student(190101,"王鹏",61,73,68);
		
		 //将学生对象添加到HashSet集合，学号重复的学生对象看作同一人，不重复添加
		 Set<Student> studentSet=new HashSet<Student>();
		 studentSet.add(stud1);
		 studentSet.add(stud2);
		 studentSet.add(stud3);
		 studentSet.add(stud4);
		 studentSet.add(stud5);
         System.out.println("未排序的学生信息：");
		 for(Student stud:studentSet) {
			 System.out.println(stud);
		 }		 
		 System.out.println();
		//将HashSet集合中的学生对象添加到TreeSet集合中，实现按总成绩降序存储
		Set<Student> studentTreeSet=new TreeSet<Student>();
		for(Student set:studentSet){
			studentTreeSet.add(set);
		}
		System.out.println("按总成绩由高到低排序：");
		for(Student stud:studentTreeSet) {
			 System.out.println(stud);
		 }
	 }
}

