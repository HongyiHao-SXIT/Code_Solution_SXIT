package com.boda.xy;
public class EnumDemo {
    public static void main(String[] args){
       // 声明一个枚举类型变量，并用一个枚举赋值
       var left = Direction.WEST;   
       System.out.println(left);   // 输出WEST
       // 输出每个枚举对象的序号
       for(var d : Direction.values()){
           System.out.println(d.name()+",序号"+d.ordinal());
       }
   }
}

