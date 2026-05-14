package com.boda.xy;
import java.util.*;
public class WildCardDemo {
	  public static void printList(List<?> list){
		  for(Object element : list){
		      System.out.println(element);
		  }
	 }
	 
    public static void main(String[] args) {
         var myList = new ArrayList<>();
		  myList.add("cat");
		  myList.add("dog");
		  myList.add("horse");
        printList(myList);
        var myList2 = new ArrayList<>();
        myList2.add(300);
		   myList2.add(500);
		   printList(myList2);		 
	 }
}

