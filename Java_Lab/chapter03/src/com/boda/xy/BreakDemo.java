package com.boda.xy;

public class BreakDemo {

	public static void main(String[] args) {
	      int n = 1;
	      int sum = 0;
	    while(n <= 100){
	        sum = sum + n;
	        if(sum > 100){
	          break;   // 若条件成立退出循环
	       }
	       n = n + 2;
	     } 
	System.out.println("n = " + n);
	System.out.println("sum = " + sum);


	}

}
