package com.boda.xy;

import java.util.Arrays;

public class SortDemo {
	public static void main(String[] args) {
		int[] a = { 75, 53, 32, 12, 46, 199, 17, 54 };
		System.out.print("排序前：");		
		System.out.println(Arrays.toString(a));
		
		Arrays.sort(a);
		System.out.print("\n排序后：");
		System.out.println(Arrays.toString(a));
	}
}
