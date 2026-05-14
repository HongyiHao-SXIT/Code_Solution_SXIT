package com.boda.xy;

import java.util.*;
import java.io.*;
public class Exercise06 {
	public static void main(String[] args){
	    String line = null;
	    String[] words = null;
	    var m = new TreeMap<String, Integer>();
	    // 创建文件输入流
	    try(
	      var br = new BufferedReader(new FileReader("proverb.txt"));
	    ){
	      while((line = br.readLine())!=null){
	        words = line.split("[ ,.]"); // 每读一行将其解析成字符串数组
	        for(String s : words){
	       	  var count = m.get(s);  // 返回单词的数量
	       	  if(count == null)           // 表示s不在m中
	      	       m.put(s,1);
	          else
	      	       m.put(s,count + 1);
	       }
	     }
	    }catch(IOException e) {}
	   System.out.println("共有" + m.size() + "个不同单词。");
	   System.out.println(m);
	}    
}
