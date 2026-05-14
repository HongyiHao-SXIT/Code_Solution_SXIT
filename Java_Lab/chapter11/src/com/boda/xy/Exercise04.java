package com.boda.xy;
import java.io.*;
import java.util.Arrays;
public class Exercise04{
   public static void main(String args[]) throws IOException{
	      try(
	         FileInputStream fis = new FileInputStream("out.dat");
	         BufferedInputStream bis = new BufferedInputStream(fis);
	         DataInputStream dis = new DataInputStream(bis);
	      ){
	        int data[] = new int[100];
	        for(int i =0;i<100;i++){
	      	   data[i]=dis.readInt();
	      	   
	        }
	        Arrays.sort(data);  // 升序排序
	        for(var n : data) {
	        	System.out.println(n);	
	        }
	      }
      System.out.println("读取数据完成！");
   }  
}