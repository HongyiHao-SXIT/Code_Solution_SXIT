package com.boda.xy;

public class Sum100 {
	public static void main(String[] args) {
		int n = 1;
		int sum = 0;
		do {
			sum = sum + n;
			n = n + 1;
		} while (n <= 100);
		System.out.println("sum = " + sum); // 输出sum = 5050
	}
}
