package com.boda.xy;

import java.util.Scanner;

@SuppressWarnings("resource")
public class ExchangeDemo {
	public static void main(String[] args) {
		Scanner input = new Scanner(System.in);
		int a ,b;
		System.out.print("请输入整数a:");
		a = input.nextInt();
		System.out.print("请输入整数b:");
		b = input.nextInt();
		
		if(a > b) {
			int temp = b;
			b = a;
			a = temp;
		}
		System.out.println("a = " + a);
		System.out.println("b = " + b);	
	}
}
