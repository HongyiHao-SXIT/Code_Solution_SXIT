package com.boda.xy;

public class MultiCatchDemo {
	public static void main(String[] args) {
		int a = 88, b = 0;
		int result = 0;
		char[] letter = { 'A', 'B', 'C' };
		for (var i = 0; i < 2; i++) {
			try {
				if (i == 0)
					result = a / b; // 产生ArithmeticException
				else
					letter[5] = 'X'; // 产生ArrayIndexOutOfBoundsException
				System.out.println(result);
			}            
			catch (ArithmeticException | ArrayIndexOutOfBoundsException me) {
				System.out.println("捕获到异常：" + me);
			}
		}
		System.out.println("处理多重捕获之后。");
	}
}
