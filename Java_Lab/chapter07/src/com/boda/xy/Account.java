package com.boda.xy;

import java.util.Objects;

public class Account {
	public int id;
	public String name;
	public double balance;

	public Account() {
	}

	public Account(int id, String name, double balance) {
		super();
		this.id = id;
		this.name = name;
		this.balance = balance;
	}

	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	public double getBalance() {
		return balance;
	}

	public void setBalance(double balance) {
		this.balance = balance;
	}

	public void deposit(double amount) {
		balance = balance + amount;
		// System.out.println("目前账户余额是：" + balance);
	}

	public boolean withdraw(double amount) {
		if (balance < amount) {
			System.out.println("账户余额不足！不能取款。");
			return false;
		} else {
			balance = balance - amount;
			// System.out.println("目前账户余额是：" + balance);
			return true;
		}
	}
	
	@Override
	public String toString() {
		return "账号:" + id + "  姓名：" + name + "  余额：" + balance;
	}

	@Override
	public boolean equals(Object obj) {
		if (obj instanceof Account)
			return this.id == ((Account) obj).id && this.name.equals(((Account) obj).name)
					&& this.balance == ((Account) obj).balance;
		else
			return false;
	}
	
	@Override
	public int hashCode() {
		return Objects.hash(id, name, balance);
	}
	
	public static void main(String[] args) {
		var account1 = new Account(108, "张明月", 5000);
		var account2 = new Account(108, "张明月", 5000);
		var account3 = new Account(108, "张明月", 4000);
		
		System.out.println(account1.hashCode());
		System.out.println(account2.hashCode());	
		System.out.println(account3.hashCode());	
	}
}
