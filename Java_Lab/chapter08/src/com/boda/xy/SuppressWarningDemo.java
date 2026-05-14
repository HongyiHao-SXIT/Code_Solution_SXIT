package com.boda.xy;

import java.io.Serializable;
import java.util.*;

@SuppressWarnings(value = { "serial", "deprecation" })
public class SuppressWarningDemo implements Serializable {
	public static void main(String[] args) {
		var d = new Date();
		System.out.println(d.getDay());

		var myList = new ArrayList<String>();
		myList.add("one");
		myList.add("two");
		myList.add("three");
		System.out.println(myList);
	}
}
