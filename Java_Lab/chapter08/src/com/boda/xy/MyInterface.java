package com.boda.xy;

public interface MyInterface {
	void normalMethod();   // 抽象方法
	
	private void init() {   
	     System.out.println("完成某些初始化操作"); 
	}
	
	default void defaultMethodA(){  
	    init(); 
	}
	
	default void defaultMethodB() { 
	    init(); 
	}
}