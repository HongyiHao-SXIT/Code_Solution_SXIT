package com.boda.xy;
import java.util.Scanner;
@SuppressWarnings("resource")
public class Palindrome {
    public static boolean isPalindrome(String s){
      var low = 0;
      var high = s.length() -1;
      while(low < high){
         if(s.charAt(low) != s.charAt(high))
            return false;
  	      low ++;
  	      high --;
  	   }
  	   return true;
     }
    public static void main(String[]args){
       var sc = new Scanner(System.in);
       System.out.print("请输入一个字符串：");
       var s = sc.nextLine();
       if(isPalindrome(s))
          System.out.println(s + "：是回文。");
       else
          System.out.println(s + "：不是回文。");
   }
}

