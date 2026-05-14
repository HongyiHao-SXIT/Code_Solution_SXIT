package com.boda.xy;

class Dog {
	int age = 2;

	public void show() {
		System.out.println(age);
	}
}

public class DogDemo {
	public static void main(String[] args) {
		int num = 10;
		System.out.println(num);
		Dog dog = new Dog();
		dog.show(); // 输出2
	}
}
