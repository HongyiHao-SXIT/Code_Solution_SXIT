package com.boda.xy;
public class Outer3{
	 private String x = "hello";
	 public void makeInner(int param){
		 final String y = "local variable";
		 final class Inner{    
		    public void show(){
	    	    System.out.println("x = " + x);
System.out.println("y = " + y);
System.out.println("param = " + param);
		    }
		 }
        new Inner().show();   
	 }
	 public static void main(String[] args){
var outer = new Outer3();
outer.makeInner(47);
	 }
}

