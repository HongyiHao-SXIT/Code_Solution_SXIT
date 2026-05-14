package com.boda.xy;

import java.io.IOException;

public class Exercise05{
	public static void methodA() throws IOException{
		System.out.println("In method A");
	}
	public static void methodB() {
		try{
			methodA();
		}catch(IOException e) {
			
		}
		System.out.println("In method B");
	}
	public static void main(String args[]){
	    methodB();	
	}
}
