package com.boda.xy;

public class BreakLabelDemo {

	public static void main(String[] args) {
		start: for (int i = 0; i < 3; i++) {
			for (int j = 0; j < 4; j++) {
				if (j == 2) {
					break start;
				}
				System.out.println(i + ":" + j);
			}
		}

	}

}
