package com.boda.xy;
import java.io.*;
import java.util.Scanner;
public class TextFileDemo{
    public static void main(String[] args){
	     var file = new File("number.txt");
       try(
var input = new FileInputStream(file);
           var sc = new Scanner(input) )
{
    	   while (sc.hasNextInt()) {
    		   int token = sc.nextInt();
    		   System.out.print(token + "  ");
    	   }
       }catch(IOException e){
		     e.printStackTrace();}	
	 }
}

