package com.boda.xy;
import java.io.*;
public class WriteDataDemo{
    public static void main(String[] args){
    	  // 向文件中写数据
try(
           var output = new FileOutputStream("data.dat");
var dataOutStream = new DataOutputStream(
    			                new BufferedOutputStream(output));
  ){
    		dataOutStream.writeDouble(123.456);     // 写8个字节
    		dataOutStream.writeInt(100);             // 写4个字节
    		dataOutStream.writeUTF("Java语言");     // 写12个字节
}catch(IOException e){
    		  e.printStackTrace();
   	  }
    	  System.out.println("数据已写到文件中。");
   }
}

