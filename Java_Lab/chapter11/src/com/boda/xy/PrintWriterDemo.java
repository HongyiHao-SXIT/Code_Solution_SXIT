package com.boda.xy;
import java.io.*;
public class PrintWriterDemo{
   public static void main(String[]args) throws IOException{
      var fileName = "number.txt";	  
      var out = new FileWriter(new File(fileName));
      var pw = new PrintWriter(out,true);
      // 向文件中随机写入10个整数
      for(int i = 0; i < 10; i++){
         int num = (int)(Math.random()*101)+100;
         pw.println(num);
      }
     pw.close();     
     // 从文件中读出10个整数
      var in = new FileReader(new File(fileName));
      var reader = new BufferedReader(in);
      var aLine = reader.readLine();
      while(aLine != null){
         System.out.print(aLine + "  ");
         aLine = reader.readLine();
      }  
      reader.close();
   }	
}

