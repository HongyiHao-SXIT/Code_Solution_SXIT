package com.boda.xy;
import java.time.LocalDate;
import java.time.Month;
import java.time.Period;
import java.time.temporal.ChronoUnit;
public class YearOfBirthDemo {
	 public static void main(String[] args) {
		var today = LocalDate.now();
		var birthday = LocalDate.of(2002, Month.DECEMBER, 19);
		var p = Period.between(birthday, today);
		// 计算两个日期之间相差的天数	
		long days = ChronoUnit.DAYS.between(birthday, today);
		System.out.println("你已出生：" + p.getYears() + " 年 , " 
		              + p.getMonths() + "个月, " + p.getDays() + "天 " );
		System.out.println("你出生的天数： " + days );
	}
}

