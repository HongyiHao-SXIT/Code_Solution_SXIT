package com.boda.xy;
import java.io.*;
public class ReadByteDemo {
     public static void main(String[] args) {  
        var inputFile = new File("output.dat");
        try(
           var in = new FileInputStream(inputFile);
) {
          int c = in.read();
          while (c != -1){
             System.out.print(c + " ");
c = in.read();
          }
       }catch(IOException e){
     System.out.println(e.toString());
}
    }
}

