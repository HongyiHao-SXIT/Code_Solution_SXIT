package com.boda.xy;

public class InitDemo {
    int x = 100 ;
    public InitDemo(){
       x = 58 ;
       System.out.println("构造方法中x =" + x);
    }	
    public static void main(String[] args){
       InitDemo d = new InitDemo();
       System.out.println("最后的x =" + d.x);
    }
    {  
       System.out.println("初始化块之前x =" + x);
        x = 60 ;
       System.out.println("初始化块中x =" + x);
    }

}
