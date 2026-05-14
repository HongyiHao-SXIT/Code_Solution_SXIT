package com.boda.xy;
import org.demo.ab.MathUtils;
public class Test {
	public static void main(String[] args) {	
		int count = 0;

		for(var n = 2; n <=1000; n++){
		   if(MathUtils.isPrime(n)&&MathUtils.isPalindrome(n)) {
			  count ++;
			  if(count %10 ==0){
				  System.out.printf("%5s%n",n);
			  }else{
				  System.out.printf("%5s",n);
			  }
		   }
	  }
	}
}
