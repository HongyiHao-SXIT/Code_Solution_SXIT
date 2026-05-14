package com.boda.xy;
import java.util.*;
public class TreeSetDemo{
	 public static void main(String[] args){
       var ts = new TreeSet<>();  // TreeSet中的元素将自动排序
       var s = new String[]{"one","two","three","four"};
       for (var i = 0; i < s.length; i++){
          ts.add(s[i]);
       }
       System.out.println(ts);
    }
}
