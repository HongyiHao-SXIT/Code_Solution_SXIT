package com.boda.xy;
import java.util.Scanner;
public class ComputeBMI {
	public static void main(String[] args) {
		double weight,height,bmi;
		
		// 第1步：读取体重和身高
		Scanner input = new Scanner(System.in);
		System.out.print("请输入体重：");
		weight = input.nextDouble();
		System.out.print("请输入身高：");
		height = input.nextDouble();
		
		// 第2步：计算BMI
		bmi = weight/(height * height);
		
		// 第3步：打印输出BMI
		System.out.println("你的BMI是" + bmi);
		input.close();
	
	}
}
