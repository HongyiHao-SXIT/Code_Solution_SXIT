package com.boda.xy;
import java.time.DayOfWeek;
import java.time.LocalDate;
public class Exercise09 {
	public static void main(String[] args) {
		LocalDate day = LocalDate.of(2000, 1, 1);
		LocalDate lastday = LocalDate.of(2099, 12, 31);
		int count = 0;
		
		while(day.isBefore(lastday)) {
		    if(day.getDayOfMonth()==13 && day.getDayOfWeek()==DayOfWeek.FRIDAY) {
		    	count ++; 
		    	System.err.println(day + "  " + day.getDayOfWeek());
		    }
		    day = day.plusDays(1);
		}
		System.err.println("13日星期五 共有 " + count + " 天");
	}
}