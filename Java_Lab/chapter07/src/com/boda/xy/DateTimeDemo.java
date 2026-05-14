package com.boda.xy;
import java.time.Instant;
import java.time.LocalDateTime;
import java.time.Month;
import java.time.ZoneId;
public class DateTimeDemo {
	public static void main(String[] args) {
		System.out.printf("现在时间: %s%n", LocalDateTime.now());
		System.out.printf("2024年10月6日 11:30am: %s%n",
		                  LocalDateTime.of(2024, Month.OCTOBER, 6, 11, 30));
		// 从当前时刻获得当前日期时间
		System.out.printf("现在时刻: %s%n",
		     LocalDateTime.ofInstant(Instant.now(), ZoneId.systemDefault()));
		// 当前时间的6个月之后和6个月之前的时间
		System.out.printf("2个月之后时间: %s%n",
		                  LocalDateTime.now().plusMonths(2));
		System.out.printf("2个月之前时间: %s%n",
		                  LocalDateTime.now().minusMonths(2));    
	}
}

