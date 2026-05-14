package com.boda.xy;

public interface BB { 
    public void show();           // 一个抽象方法
    public default void print(){       
         System.out.println("这是BB接口的默认方法");
   }
}