package com.boda.xy;
public class CharBoolDemo{
    public static void main(String[] args){
       boolean b;
       char ch1,ch2;
       ch1 = 'Y';
       ch2 = 65;              
       System.out.println("ch1 = "+ch1+",ch2 = "+ch2);
       b = ch1==ch2;            
       System.out.println(b);
       ch2 = (char)(ch2 + 1);                  
       System.out.println("ch2="+ch2);  
}
}

