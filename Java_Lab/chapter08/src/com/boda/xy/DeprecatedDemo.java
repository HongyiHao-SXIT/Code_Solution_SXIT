package com.boda.xy;

import java.util.Date;
import java.time.LocalDate;

public class DeprecatedDemo {
	@Deprecated
	public Date today() { // Eclipse中废弃方法加删除线
		var d = new Date();
		return d;
	}

	public LocalDate getToday() {
		var d = LocalDate.now();
		return d;
	}

	public static void main(String[] args) {
		var dd = new DeprecatedDemo();
		System.out.println(dd.today()); // 调用时也加删除线
		System.out.println(dd.getToday());
	}
}
