package com.boda.xy;
public class Exercise04 {
	public static int countLetters(String s) {
		int n = 0;
		for (int i = 0; i < s.length(); i++) {
			char c = s.charAt(i);
			if (c >= 'a' && c <= 'z' || c >= 'A' && c <= 'Z')
				n = n + 1;
		}
		return n;
	}
	public static void main(String[] args) {
		int n = countLetters("Beijing 2008");
		System.out.println(n);
	}
}
