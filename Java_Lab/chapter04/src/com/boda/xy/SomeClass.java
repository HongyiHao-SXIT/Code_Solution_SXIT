package com.boda.xy;
public class SomeClass{
   int x = 5;
   static int y = 48;
   // 静态方法的定义
   public static void display(){
      SomeClass instance = new SomeClass();
      y = y + 100;  
      System.out.println("y = "+ y);
      instance.x = instance.x * 5 ; 
      System.out.println("x = "+ instance.x);
   }
}

