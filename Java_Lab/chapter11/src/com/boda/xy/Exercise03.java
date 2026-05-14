package com.boda.xy;
import java.io.*;
public class Exercise03{
   public static void main(String args[]) throws IOException{
      try(
	      FileOutputStream fos = new FileOutputStream("out.dat");
           BufferedOutputStream bos = new BufferedOutputStream(fos);
           DataOutputStream dos = new DataOutputStream(bos);
        ){
    	  for(int i = 0;i<100;i++){
      	      int num = (int)(Math.random()*1001)+1000;
      	      System.out.println(num);
      	      dos.writeInt(num);
          }      
      }
      System.out.println("写入数据完成！");
   }  
}