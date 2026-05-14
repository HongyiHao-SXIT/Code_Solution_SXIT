package com.boda.xy;
import java.util.Scanner;
@SuppressWarnings("resource")
public class SwitchDemo{
   public static void main(String[] args) {
       Scanner input = new Scanner(System.in);
       System.out.print("输入一个季节（1,2,3,4）：");
       int season = input.nextInt();  
       switch (season) {
           case 1 -> System.out.println("春雨惊春清谷天");
           case 2 -> System.out.println("夏满忙夏暑相连");
           case 3 -> System.out.println("秋处露秋寒霜降");
           case 4 -> System.out.println("冬雪雪冬小大寒");          
           default ->
                System.out.println("季节输入非法.");
         }
    }
}

