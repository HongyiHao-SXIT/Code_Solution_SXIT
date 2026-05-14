package com.boda.xy;
public class VarargsDemo{
    public static double average(double ... values){
  	     var sum = 0.0;
  	     for(var value:values){
  	          sum = sum + value;   // 求数组元素之和
  	     }
  	     var average = sum / values.length;
  	     return average;
    }
    public static void main(String[] args){
       System.out.println("平均值 = " + average(60,70,86,80));        
    }	
}

