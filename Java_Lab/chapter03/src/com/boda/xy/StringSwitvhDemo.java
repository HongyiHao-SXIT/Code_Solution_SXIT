package com.boda.xy;

import java.util.Scanner;

@SuppressWarnings("resource")
public class StringSwitvhDemo {
	public static void main(String[] args) {
		String season = "";
		Scanner input = new Scanner(System.in);
		System.out.print("请输入英文季节名称：");
		season = input.next();
		switch (season.toLowerCase()) {
		case "spring" -> System.out.println("春天");
		case "summer" -> System.out.println("夏天");
		case "autumn" -> System.out.println("秋天");
		case "winter" -> System.out.println("冬天");
		default -> System.out.println("输入名称错误！");
		}
	}
}
