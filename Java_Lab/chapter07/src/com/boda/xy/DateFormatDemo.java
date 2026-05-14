package com.boda.xy;
import java.time.DateTimeException;
import java.time.LocalDateTime;
import java.time.Month;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
public class DateFormatDemo {
	public static void main(String[] args) {
		var leavingZone = ZoneId.of("America/Los_Angeles");;
		var departure = LocalDateTime.of(2022,Month.JULY,20,19,30);
		var arrvingZone = ZoneId.of("Asia/Shanghai");
		var arrive = LocalDateTime.of(2022,Month.JULY,21,22,20);
		// 将本地日期时间格式化
		try {
		   var format = 
		DateTimeFormatter.ofPattern("MMM d yyyy  hh:mm a");
		    String out = departure.format(format);
		    System.out.printf("出发日期： %s (%s)%n", out, leavingZone);
		    out = arrive.format(format);
		    System.out.printf("到达日期： %s (%s)%n", out, arrvingZone);
		}catch (DateTimeException exc) {
		    System.out.printf("%s can't be formatted!%n", departure);
		    throw exc;
		}
	}
}

