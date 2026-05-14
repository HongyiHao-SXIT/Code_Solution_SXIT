package com.boda.xy;

public class ContinueDemo {

	public static void main(String[] args) {
		start:
			for(int i = 0; i < 3; i++){
			   for(int j = 0; j < 4; j++){
			       if(j == 2){
			          continue start;    
			}
			      System.out.println(i +" : " + j);
			}
			}


	}

}
