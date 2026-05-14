package com.boda.xy;
public class Account {
    private int id;
    private String name;
    private double balance;
    
    public Account() {    	
    }    
    public Account(int id, String name, double balance) {
		super();
		this.id = id;
		this.name = name;
		this.balance = balance;
	}
	public int getId() {
		return id;
	}
	public void setId(int id) {
		this.id = id;
	}
	public double getBalance() {
		return balance;
	}
	public void setBalance(double balance) {
		this.balance = balance;
	}
	public String getName() {
    	return name;
    }
    
    public void setName(String name) {
        this.name = name;	
    }     
    public void deposit(double amount) {
    	balance = balance + amount;
    	System.out.println("目前账户的余额是："+balance);
    }   
    public boolean withdraw(double amount) {
    	if(balance >= amount) {
    		balance = balance - amount;
        	System.out.println("目前账户的余额是："+balance);
        	return true;
    	}else {
    		System.out.println("账户余额不足！不能取款。");
    		return false;
    	}
    }   
}
