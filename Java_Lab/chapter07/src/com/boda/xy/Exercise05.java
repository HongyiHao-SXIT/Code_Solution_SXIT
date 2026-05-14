package com.boda.xy;
public class Exercise05 {
	public static void main(String args[]) {
		String s = "no pains,no gains";
		String[] words = s.split("[, ]");
		for (int i = 0; i < words.length; i++)
			System.out.println(words[i]);
	}
}
