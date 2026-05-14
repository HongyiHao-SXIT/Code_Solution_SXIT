package com.boda.xy;

public class ArrayDemo2 {
	public static double sumArray(double array[]){
	    var sum = 0.0;
	    for(var i = 0; i < array.length; i++){
	       sum = sum + array[i];
	   }  
	  return sum;
	}
	
	public static int[] reverse(int[] list){
		   var result = new int[list.length]; 
		   for(int i=0,j=result.length-1;i<list.length;i++,j--) {
		      result[j] = list[i];   
		   }
		   return result;  
		}

	public static void main(String[] args) {
//		double[] source = {10,30,20,40};
//        System.out.println(sumArray(source));
//        
        int[] list = {6,7,8,9,10};
        int [] list2 = reverse(list);
        for(int n:list2) {
        	System.out.print(n + "  ");
        }
	}

}
