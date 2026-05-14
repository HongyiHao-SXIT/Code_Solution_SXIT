package com.boda.xy; 
public class PrintStars {
	public static void main(String[] args) {	
      // i记录每行打印的星号数
	    for (int n = 1; n <= 8; n ++) {		
		    // 打印每行的前导空格
            for (int k = 1; k <= (8 - n); k++) {
			    System.out.print(" ");
			} 
         // 每行打印i个星号
		    for (int j = 1; j <= (2*n-1); j++) {
			    System.out.print("*");
		    }
		    System.out.println();      // 换行
	    }
    }
}
