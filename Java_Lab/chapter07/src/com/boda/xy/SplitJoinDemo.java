package com.boda.xy;
public class SplitJoinDemo {
   public static void main(String[]args) {
	    var ss= "one little,two little,three little.";
	    var str = ss.split("[ ,.]");  
	    for(var s : str){
	        System.out.println(s);
	    }
	    System.out.println(ss.matches(".*little.*"));  // 输出true
      // 连接字符串
	    var  joined = String.join("\\","C:","javastudy","com");
	    System.out.println(joined);              //  输出usr/local/bin
	    String [] seasons = {"春", "夏", "秋", "冬"};
	    var s = String.join("-" ,seasons);
	    System.out.println(s);                    // 输出春-夏-秋-冬
   }  
}

