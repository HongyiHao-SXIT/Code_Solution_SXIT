package com.boda.xy;

import java.util.Scanner;

@SuppressWarnings("resource")
public class CustomExceptionDemo {
	public static void main(String[] args) {
		Scanner input = new Scanner(System.in);
		double radius = 0, area = 0;
		System.out.print("请输入半径值：");
		try {
			radius = input.nextDouble();
			if (radius < 0) {
				throw new NegativeValueException("半径值不能小于0.");
			} else {
				area = Math.PI * radius * radius;
				System.out.println("圆的面积是：" + area);
			}
		} catch (NegativeValueException nve) {
			System.out.println(nve.getMessage());
		}
	}
}
