package com.boda.xy;
import java.util.Scanner;
@SuppressWarnings("resource")
public class SwitchExprDemo {
	public static void main(String[] args) {
		Scanner input = new Scanner(System.in);
		System.out.print("输入一个年份：");
		int year = input.nextInt();
		System.out.print("输入一个月份：");
		int month = input.nextInt();
		
		int numDays = switch (month) {
		   case 1, 3, 5, 7, 8, 10, 12 -> 31;
		   case 4, 6, 9, 11 -> 30;
		   // 对2月需要判断是否是闰年
	  	   case 2 -> {
			   if (((year % 4 == 0) && !(year % 100 == 0)) || (year % 400 == 0))
				   yield 29;
			   else
				   yield 28;
		   }
		   default -> 0;
		};  
		System.out.println("该月的天数为：" + numDays);
	}
}

