package com.boda.xy;

import java.io.FileInputStream;
import java.io.IOException;
import java.io.PrintWriter;
import java.util.Scanner;
public class Exerciser05 {
    public static void main(String[]args) { 	
        try{      	
         Scanner input = new Scanner(System.in);
         PrintWriter output = new PrintWriter("out.txt");   	
         System.out.print("请输入一个整数"); 
         int n = input.nextInt();
         System.out.print("请输入一个浮点数");
         double d = input.nextDouble();
         System.out.print("请输入一个字符串");
         String s = input.next();
         output.println(n);
         output.println(d);
         output.println(s);
         output.close();
         
         Scanner out = new Scanner(new FileInputStream("out.txt"));	
         System.out.println(out.nextInt());
         System.out.println(out.nextDouble());
         System.out.println(out.next());
         out.close();
         input.close();
        }catch(IOException e) {
        	e.printStackTrace();
        }
    }	  
}
