package com.boda.xy;

import java.util.Arrays;
import java.util.Collections;

public class SortDemo {

	public static void main(String[] args) {
		var numbers = Arrays.asList(20, -5, 49, 8);
		Collections.sort(numbers);
		for(var n :numbers){
		     System.out.println(n);
		}


	}

}
