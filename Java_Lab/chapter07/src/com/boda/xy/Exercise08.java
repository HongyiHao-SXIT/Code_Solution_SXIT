package com.boda.xy;
import java.time.LocalDate;
public class Exercise08 {
	public static void main(String[] args) {
	LocalDate pDay = LocalDate.of(2022, 1, 1).plusDays(255);
	System.out.println(pDay);    // 2022-09-13
}
}
