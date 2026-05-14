package com.boda.xy;
import java.util.*;
@SuppressWarnings("resource")
public class Exercise01{
   public static void main(String args[]){
      Scanner sc = new Scanner(System.in);
      System.out.print("请输入半径值：");
      try{
         double radius = sc.nextDouble();
         double area = Math.PI*radius*radius;
         System.out.printf("area = %.2f%n",area);
      }catch(InputMismatchException e){
         System.out.println(e);
         System.out.println("数字格式不正确!");
     }
  }
}

