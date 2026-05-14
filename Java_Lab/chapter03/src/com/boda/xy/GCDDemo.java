package com.boda.xy;

import java.util.Scanner;

@SuppressWarnings("resource")
public class GCDDemo {
	public static void main(String[] args) {
		Scanner input = new Scanner(System.in);
		System.out.print("请输入第1个整数:");
		int m = input.nextInt();
		System.out.print("请输入第2个整数:");
		int n = input.nextInt();
		// 求m和n的最大公约数
		int gcd = 1;
		int k = 2;
		while (k <= m && k <= n) {
			if (m % k == 0 && n % k == 0)
				gcd = k;
			k++;
		}
		System.out.println(m + " 与 " + n + " 的最大公约数是" + gcd);
	}
}
