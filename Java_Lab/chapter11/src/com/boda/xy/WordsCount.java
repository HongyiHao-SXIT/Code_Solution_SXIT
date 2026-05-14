package com.boda.xy;
import java.io.*;
public class WordsCount{
   public static void main(String[] args) throws Exception{
      var fileName = "article.txt";
      var inFile = new FileReader(fileName);
      var reader = new BufferedReader(inFile);
      int sum = 0;
      String []words;
      var aLine = reader.readLine();
      while(aLine != null){
         words = aLine.split("[ ,.]");
         sum = sum + words.length;
         aLine = reader.readLine();
      }  
      reader.close();
      System.out.println("共有单词数 = "+sum);
   }
}

