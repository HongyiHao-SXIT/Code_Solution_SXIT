package com.boda.xy;

interface Printable {
	public void print(String message);
}

public class PrintableTest {
	public static void main(String[] args) {
		var printer = new Printable() {
			@Override
			public void print(String message) {
				System.out.println(message);
			}
		};
		printer.print("这是惠普打印机");
	}
}
