package com.boda.xy;

import java.util.Scanner;

public class ExceptionDemo {

	public static void main(String[] args) {
		try (Scanner input = new Scanner(System.in)) {
			System.out.print("请输入被除数和除数：");
			int a = input.nextInt();
			int b = input.nextInt();
			System.out.println("结果=" + (a / b));
		} catch (ArithmeticException ex) {
			System.out.println("除数不能为0。");
		}
	}

}
