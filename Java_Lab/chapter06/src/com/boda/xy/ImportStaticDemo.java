package com.boda.xy;
import static java.lang.Math.*;
import static java.lang.System.*;
public class ImportStaticDemo{
    public static void main(String[] args){
       var d = random();     // 直接使用Math类的静态方法，不需加类名前缀
       var pi = PI;           // 直接使用Math类的静态成员
       out.println("d = "+d);    // out是System类的一个静态成员
       out.println("pi = "+pi);
    }	
}

