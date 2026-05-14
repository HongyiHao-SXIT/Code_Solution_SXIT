package com.boda.xy;
import java.util.Scanner;
@SuppressWarnings("resource")
public class Exercise01{
   public static void main(String[] args) {
	   String s;
	   Scanner input = new Scanner(System.in);
	   System.out.println("请输入一个字符串：");
	   s = input.nextLine();
	   System.out.println("字符串长度："+ s.length());
	   System.out.println("第一个字符是："+ s.charAt(0));
	   System.out.println("最后一个字符是："+ s.charAt(s.length()-1));
	   System.out.println("大写形式："+ s.toUpperCase());
   }
}

