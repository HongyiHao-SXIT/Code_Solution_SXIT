package com.boda.xy;

import java.util.Scanner;

@SuppressWarnings("resource")
public class ComputeBMI {
	public static void main(String[] args) {
		Scanner input = new Scanner(System.in);
		double weight, height;
		double bmi;
		System.out.print("请输入你的体重（单位：公斤）：");
		weight = input.nextDouble();
		System.out.print("请输入你的身高（单位：米）：");
		height = input.nextDouble();
		bmi = weight / (height * height);
		System.out.println("你的身体质量指数是：" + bmi);
		
		if (bmi < 18.5) {
			System.out.println("你的体重偏瘦。");
		} else if (bmi < 25.0) {
			System.out.println("你的体重正常。");
		} else if (bmi < 30.0) {
			System.out.println("你的体重超重。");
		} else {
			System.out.println("你的体重过胖。");
		}
	}
}
