package com.boda.xy;

public class AccountDemo {
	public static void main(String[] args) {
		Account myAccount; // 声明一个Account类型的引用变量
		myAccount = new Account(); // 调用构造方法创建对象
		myAccount.id = 1001;
		myAccount.name = "李明月";
		myAccount.deposit(5000.00);
		myAccount.withdraw(3000.00);
// 输出账户信息
		System.out.println("账户ID = " + myAccount.id);
		System.out.println("姓名 = " + myAccount.name);
		System.out.println("余额 = " + myAccount.balance);
	}
}
