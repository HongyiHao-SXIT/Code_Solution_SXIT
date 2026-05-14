package com.boda.xy;

public class LogicalDemo {
	public static void main(String[] args){
		int a = 1, b = 2, c =3 ;
			    boolean u = false;
		u = (a >= --b || b++ < c--) && b == c;
			    System.out.println("u = " + u);
			    // 使用&和|运算符
			    b = 2;
		u = ( a >= --b | b++ < c--) & b == c;
			    System.out.println("u = "+u);		
		   }

}
