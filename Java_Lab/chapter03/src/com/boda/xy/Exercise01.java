package com.boda.xy;
public class Exercise01{
	public static void main(String[] args) {
		int n = args.length > 0 ? Integer.parseInt(args[0]) : 0;
		int sum = 0;
		while(n > 0){
		   sum = sum + n%10;
		   n = n / 10;
		}
		System.out.println("各位数字之和为：" + sum);
	}
}


