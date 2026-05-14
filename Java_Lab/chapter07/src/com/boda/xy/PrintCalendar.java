package com.boda.xy;
import java.util.Scanner;
import java.util.Locale;
import java.time.LocalDate;
import java.time.format.TextStyle;

@SuppressWarnings("resource")
public class PrintCalendar {
	public static void main(String[] args) {
		var input = new Scanner(System.in);
		System.out.print("请输入一个年份(如2022):");
		var year = input.nextInt();
		for (var month = 1; month <= 12; month++) {
			// 得到本月第一天日期
			var dates = LocalDate.of(year, month, 1);
			String monthName = dates.getMonth().getDisplayName(TextStyle.FULL, Locale.getDefault());
			// 返回当前月的天数
			var daysOfMonth = dates.lengthOfMonth();
			System.out.println(year + "年        " + monthName);
			System.out.println("-----------------------------");
			System.out.printf("%4s%3s%4s%3s%4s%3s%4s%n", "一", "二", "三", "四", "五", "六", "日");
			// 返回1月1日是周几，返回1是周一，返回7是周日
			var dayOfWeek = dates.getDayOfWeek().getValue();
			// 输出前导空格，如果是周一（dayOfWeek值为1）不输出空格
			for (var i = 2; i <= dayOfWeek; i++)
				System.out.printf("%4s", " ");
			// 输出该月的日期
			for (var i = 1; i <= daysOfMonth; i++) {
				System.out.printf("%4d", i);
				if ((dayOfWeek + i - 1) % 7 == 0)
					System.out.println(); // 换行
			}
			System.out.printf("%n%n");
		}
	}
}
