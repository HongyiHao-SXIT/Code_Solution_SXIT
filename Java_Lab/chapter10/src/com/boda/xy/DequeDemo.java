package com.boda.xy;

import java.util.*;

public class DequeDemo {
	public static void main(String[] args) {
		var elements = new int[] { 1, 2, 3, 0, 7, 8, 9 };
		var queue = new ArrayDeque<>();
		// 将元素5添加到队列中
		queue.addFirst(5);
		// 将数组中前3个元素添加到queue中
		for (var i = 0; i < 3; i++)
			queue.addFirst(elements[i]);    //[3, 2, 1, 5]
		System.out.println(queue);
		// 将数组中后3个元素添加到queue中
		for (var i = 4; i < 7; i++)
			queue.offerLast(elements[i]);   // [3, 2, 1, 5, 7, 8, 9]
		System.out.println(queue);
		// 访问queue中每个元素
		for (var v : queue)
			System.out.print(v + "  ");
		System.out.println("\nsize = " + queue.size());
	}
}
