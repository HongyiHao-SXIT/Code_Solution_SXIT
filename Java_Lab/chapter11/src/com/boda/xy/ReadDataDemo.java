package com.boda.xy;
import java.io.*;
public class ReadDataDemo{
    public static void main(String[] args){
    	   // 从文件中读取数据
	      try(
           var input = new FileInputStream("data.dat");
	         var dataInStream = new DataInputStream(
	                 new BufferedInputStream(input));
   ){        
		   while(dataInStream.available()>0){
		       double d = dataInStream.readDouble();
		       int i = dataInStream.readInt();
		       String s = dataInStream.readUTF();
		       System.out.println("d = "+d);
		       System.out.println("i = "+i);
		       System.out.println("s = "+s);
		   }
	   }catch(IOException e){
	       e.printStackTrace();
	   }
}
 }

