package com.boda.xy;
public class PassByValue {
    public static void changeValue(int num){
        num = 200;
        System.out.println(num);     // 输出200
    }

    public static void changeValue(Account accnt){
        accnt.setBalance(10000);
    }
      
    public static void main(String[]args){
       Account account = new Account();
       account.setBalance(8000);
       System.out.println(account.getBalance()); // 80000
       changeValue(account);
      
       System.out.println(account.getBalance());  // 10000    
  
    }    
}

