package com.boda.xy;
import java.util.*;
public class Exercise05{
  public static void main(String args[]){
    int elem[]= new int[20];
    HashSet<Integer> hs=new HashSet<>();
    TreeSet<Integer> ts=new TreeSet<>();
    
    for(int i=0;i<20;i++){
      elem[i]=(int)(Math.random()*90+10);
      //System.out.print(elem[i] + "  ");
      hs.add(elem[i]);
      ts.add(elem[i]);
    }
    for(Integer n : hs) {
    	 System.out.print(n + "  ");
    }
    System.out.println();
	Iterator<Integer> it = ts.iterator();
	while(it.hasNext()){
		Integer n = (Integer)it.next();
		System.out.print(n + "  ");
	}
  }
}
