package com.boda.xy;

import java.util.Scanner;

@SuppressWarnings("resource")
public class LeapYear {
	public static void main(String[] args) {
		Scanner scan = new Scanner(System.in);
		System.out.print("请输入年份：");
		int year = scan.nextInt();
		
		if (year % 400 == 0 || (year % 4 == 0 && year % 100 != 0)) {
			System.out.println(year + " 年是闰年。");
		} else {
			System.out.println(year + " 年不是闰年。");
		}
	}
}
