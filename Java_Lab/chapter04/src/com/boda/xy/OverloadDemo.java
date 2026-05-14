package com.boda.xy;
public class OverloadDemo{
    public void display (double d){
       System.out.println("d = "+d);	
    }	
    public void display(){
       System.out.println("无参数方法");	
    }	
    public void display(int a,int b){
       System.out.println("a = "+a+",b = "+b);	
    }	
    // 测试方法重载
    public static void main(String[] args){
  	    OverloadDemo obj = new OverloadDemo();
  	    obj.display();
  	    obj.display(10);
  	    obj.display(50,60);
  	    obj.display(100.0);
    }	
}
