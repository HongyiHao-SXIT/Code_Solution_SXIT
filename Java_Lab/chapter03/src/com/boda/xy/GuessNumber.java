package com.boda.xy;
import java.util.Scanner;
@SuppressWarnings("resource")
public class GuessNumber{
   public static void main(String[] args){
      int magic = (int)(Math.random()*100)+1;
      Scanner sc = new Scanner(System.in);
      System.out.print("我想出一个1-100的数，请你猜：");
      int guess = sc.nextInt(); 
      while(guess != magic){
         if(guess > magic)
           System.out.print("对不起!太大了，请重猜：");
         else
           System.out.print("对不起!太小了，请重猜：");
         guess = sc.nextInt(); 
      }
      System.out.println("恭喜你，答对了！\n该数是："+magic);
   }
}

