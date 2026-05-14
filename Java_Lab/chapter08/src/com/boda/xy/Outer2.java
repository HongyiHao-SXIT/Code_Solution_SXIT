package com.boda.xy;
public class Outer2{
     private static int x = 100;
private int m = 200;
public static class Inner{           
         private String y = "hello";
         public void innerMethod(){
            System.out.println("x = "+ x);      
System.out.println("y = "+ y);
        }
     } 
   public static void main(String[] args){
       // 不需要外层类的实例就可以直接创建一个静态内部类实例
var inner = new Outer2.Inner();
        inner.innerMethod(); 
   		System.out.println("m = " + new Outer2().m);
     }
}

