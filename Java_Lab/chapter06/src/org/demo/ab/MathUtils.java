package org.demo.ab;

public final class MathUtils {
	private MathUtils() {
	}

	public static boolean isPrime(int number) {
		if (number < 2) {
			return false;
		}
		for (int divisor = 2; divisor * divisor <= number; divisor++) {
			if (number % divisor == 0) {
				return false;
			}
		}
		return true;
	}

	public static boolean isPalindrome(int number) {
		int original = number;
		int reversed = 0;
		while (number > 0) {
			reversed = reversed * 10 + number % 10;
			number /= 10;
		}
		return original == reversed;
	}
}
