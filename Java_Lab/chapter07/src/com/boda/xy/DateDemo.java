package com.boda.xy;

import java.time.LocalDate;
import java.time.Month;

public class DateDemo {
	public static void main(String[] args) {
		var today = LocalDate.now();
		System.out.println("今天的日期是：" + today);
		System.out.println("年：" + today.getYear());
		System.out.println("月：" + today.getMonthValue());
		System.out.println("日：" + today.getDayOfMonth());
		System.out.println("星期：" + today.getDayOfWeek());

		var birthday = LocalDate.of(2010, Month.OCTOBER, 20);
		System.out.println("我的出生日期是：" + birthday);
		System.out.println(birthday.getYear() + "年" + (birthday.isLeapYear() ? "是闰年" : "不是闰年"));
	}
}
