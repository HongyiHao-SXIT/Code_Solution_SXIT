package com.boda.xy;

import java.util.Scanner;

@SuppressWarnings("resource")
public class Decryption {
	public static void main(String[] args) {
		var sc = new Scanner(System.in);
		System.out.print("请输入加密字符串:");
		var str = sc.nextLine();
		System.out.println("加密字符串是 :" + str);
		StringBuilder ss = new StringBuilder(str);
		
		for (int i = 0; i < ss.length(); i++) {
			char c = ss.charAt(i);
			if (c >= 'A' && c <= 'Z' || c > 'a' && c <= 'z') {
				if (c >= 'A' && c <= 'E' || c >= 'a' && c <= 'e') {
					c = (char) (c + 21);
				} else {
					c = (char) (c - 5);
				}
			}

			ss.setCharAt(i, c);
		}
		System.out.println("解密后的字符串是:" + ss);
	}
}
