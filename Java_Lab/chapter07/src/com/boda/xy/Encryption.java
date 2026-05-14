package com.boda.xy;

import java.util.Scanner;

@SuppressWarnings("resource")
public class Encryption {
	public static void main(String[] args) {
		var sc = new Scanner(System.in);
		System.out.print("请输入一个字符串:");
		var str = sc.nextLine();
		System.out.println("原字符串是 :" + str);
		StringBuilder ss = new StringBuilder(str);

		for (int i = 0; i < ss.length(); i++) {
			char c = ss.charAt(i);
			if (c >= 'A' && c <= 'Z' || c > 'a' && c <= 'z') {
				if (c > 'U' && c <= 'Z' || c > 'u' && c <= 'z') {
					c = (char) (c - 21);
				} else {
					c = (char) (c + 5);
				}
			}
			ss.setCharAt(i, c);
		}
		System.out.println("加密后的字符串是:" + ss);
	}

}
