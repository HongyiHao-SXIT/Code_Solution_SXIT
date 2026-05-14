package com.boda.xy;
import java.time.Clock;
import java.time.Instant;
public class InstantDemo {
	public static void main(String[] args) {
		var timestamp = Instant.now();
		System.out.println(timestamp);
		var oneHourLater = Instant.now().plusSeconds(60*60);
		System.out.println(oneHourLater);
		final Clock clock = Clock.systemUTC();         // 返回系统时钟时刻
		Instant instant = clock.instant();
		Instant now = Instant.now().plusSeconds(5);
		System.out.println(instant.isBefore(now));     // 输出true
	}
}
