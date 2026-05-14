package com.boda.xy;
import java.util.*;
public class BoundedTypeDemo {
   public static double getAverage(List<? extends Number> numberList){
	    var total = 0.0;
	    for(var number :numberList){
		    total += number.doubleValue();
	    }
	    return total/numberList.size();
   }
   
   public static void main(String[] args) {
	    var integerList = new ArrayList<Integer>();
	    integerList.add(3);
	    integerList.add(30);
	    integerList.add(300);
	    System.out.println(getAverage(integerList));  // 111.0
	    var doubleList = new ArrayList<Double>();
	    doubleList.add(5.5);
	    doubleList.add(55.5);
	    System.out.println(getAverage(doubleList));  // 30.5
	}
}

