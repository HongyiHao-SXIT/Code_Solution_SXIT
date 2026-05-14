package com.boda.xy;
import java.math.*;
public class LargeFactorial{
   public static BigInteger factorial(long n){
      var result = BigInteger.ONE;  // BigInteger.ONE常量，表示1
      for (long i = 1; i <= n; i++){
         result = result.multiply(new BigInteger(i+""));
      }
      return result;
   }
   public static void main(String[]args){
       System.out.println("50! is \n" + factorial(50));
   }
}

