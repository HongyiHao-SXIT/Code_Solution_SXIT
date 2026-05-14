package com.boda.xy;

public class Outer {
	private int x = 200;

	private class Inner {
		int y = 300;

		public int sum() {
			return x + y; // 可以访问外层类的成员x
		}
	}

	public void makeInner() {
		var ic = new Inner(); // 创建内部类对象
		System.out.println(ic.sum());
	}

	public static void main(String[] args) {
		var outer = new Outer();
		outer.makeInner();
		var inner = outer.new Inner();
		System.out.println(inner.sum()); // 输出500
	}
}
