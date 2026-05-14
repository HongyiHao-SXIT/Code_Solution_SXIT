package com.boda.xy;
import java.util.Scanner;
@SuppressWarnings("resource")
public class ATMDemo {
	public static void main(String[] args) {
		Scanner input = new Scanner(System.in);
		int id = 0;
		int choice = 0;
		String menu = "     主  菜  单\n"
				+ "查余额------1  存款----2\n"
				+ "取  款------3  退出----4";
		Account [] accounts = new Account[10] ;
		for(int i=0;i<accounts.length;i++){
			accounts[i] = new Account(100+i, "user10"+i ,100);
		}
		
		while(true) {
			System.out.print("请输入你的账号：");
			id = input.nextInt();
			Account curAccount = new Account();			
			if(id >=100 && id<=109) {
				inner:
				while(true) {
				  for(int i=0;i<accounts.length;i++){
					if(accounts[i].getId()== id) {
						curAccount = accounts[i];
						break;
					}
				  }
				System.out.println();	
				System.out.println(menu);
				System.out.print("请输入选项：");
				choice = input.nextInt();
				switch(choice) {
				  case 1 ->  {
					  System.out.println("账户名：" + curAccount.getName());
					  System.out.println("账户目前余额是：" + curAccount.getBalance()+"元");
					   
				  }
                  case 2 ->  {
                	  System.out.print("请输入存款金额："); 
                	  double amount = input.nextDouble();
                	  curAccount.deposit(amount);
				  }
                  case 3 ->  {
                  	  System.out.print("请输入取款金额："); 
                	  double amount = input.nextDouble();
                	  curAccount.withdraw(amount); 
                  }
                  case 4 ->  {
                	  
                	  break inner;  
                  }
				}
			 } //end while
			}else{
				System.out.println("账号不存在，请重新输入！");	
			}			
		}
	}
}
