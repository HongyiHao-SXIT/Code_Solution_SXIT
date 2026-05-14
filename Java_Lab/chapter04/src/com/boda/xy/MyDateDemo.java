package com.boda.xy;
import com.boda.MyDate;

public class MyDateDemo {
	public static void main(String[] args) {
	    MyDate today = new MyDate();
	    System.out.println(today.toString());
	    
	    MyDate birthday = new MyDate(2002,10,6);
	    System.out.println(birthday.getYear() + "年" 
	        + (birthday.isLeapYear() ? "是闰年" : "不是闰年"));
	    System.out.println("你已出生" + today.between(birthday) + "天。");

	}

}
