package com.boda.xy;
public class StringSort{
     public static void main(String[] args){
         String []str = {"中国","美国","俄罗斯","法国","英国"};
         System.out.print("排序前：");
        for(var s: str)
            System.out.print(s+" ");
        for(var i = str.length-1; i >= 0; i--)
            for(var j = 0; j < i; j++){
               if(str[j].compareTo(str[j+1])>0){
                  String temp = str[j];
                  str[j] = str[j+1];
                  str[j+1] = temp;
               }
         }
         System.out.print("\n排序后：");
         for(var s: str)
            System.out.print(s+" ");
     }
}
