package com.boda.xy;

import java.util.Random;

public class IntStackDemo {
	public static void main(String[] args) {
		var stack = new IntStack();
		var rand = new Random();
        // 向栈中存入11个随机生成的整数
		for (int i = 0; i <= 10; i++) {
			var n = Integer.valueOf(rand.nextInt(100));
			System.out.print(n + " ");
			stack.push(n);
		}
		System.out.println();
        
		// 弹出栈中的所有元素
		System.out.println("栈大小为： " + stack.getSize());
		while (!stack.empty()) {
			System.out.print(stack.pop() + " ");
		}
	}
}
