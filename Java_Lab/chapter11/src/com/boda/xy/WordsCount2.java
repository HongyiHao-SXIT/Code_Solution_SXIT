package com.boda.xy;
import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.util.Scanner;
@SuppressWarnings("resource")
public class WordsCount2 {
    public static void main(String args[]){
    	 var input = new Scanner(System.in);
    	 var filename = "";
    	 System.out.print("请输入文本文件名: ");
    	 filename = input.nextLine(); 	   
    	 var file = new File(filename);
 	     if(!file.exists()) {
		    System.out.println("您输入的文件不存在！ ");
		    System.exit(0);
	     }
     	 try(
    	     var fis = new BufferedReader(new FileReader(file));	   
    	 ){
         var charNums = 0;
         var wordsNums = 0;
         var lineNums = 0;
         var aLine= fis.readLine();
         while(aLine != null){
              charNums = charNums + aLine.length();
              var words = aLine.split("[ ,;.!?]");   // 6个符号
              wordsNums = wordsNums + words.length;
              lineNums = lineNums + 1;	
              aLine = fis.readLine();
         }
         System.out.println("文件 = "+filename );
         System.out.println("总共字符数 = "+charNums +"个");
         System.out.println("单词数 = "+wordsNums +"个" );
         System.out.println("共有行数 = "+lineNums +"行");   
       }catch(IOException ioe) {
    	    ioe.printStackTrace();
       }    	
    }
}

