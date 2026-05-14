package com.boda;

import java.time.LocalDate;
import java.time.temporal.ChronoUnit;

public class MyDate {
	private final LocalDate date;

	public MyDate() {
		this(LocalDate.now());
	}

	public MyDate(int year, int month, int day) {
		this(LocalDate.of(year, month, day));
	}

	private MyDate(LocalDate date) {
		this.date = date;
	}

	public int getYear() {
		return date.getYear();
	}

	public boolean isLeapYear() {
		return date.isLeapYear();
	}

	public long between(MyDate other) {
		return ChronoUnit.DAYS.between(other.date, date);
	}

	@Override
	public String toString() {
		return date.toString();
	}
}
