package com.boda.xy;

public interface SS {
	int STATUS = 100;

	public static void display() {
		System.out.println(STATUS);
	}

	public static void main(String []args) {
		SS.display();
	}
}
