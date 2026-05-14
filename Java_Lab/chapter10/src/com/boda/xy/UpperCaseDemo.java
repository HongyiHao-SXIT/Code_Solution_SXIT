package com.boda.xy;

import java.util.ArrayList;
import java.util.Iterator;
public class UpperCaseDemo {
	public static void main(String[] args) {
		ArrayList<String> names = new ArrayList<>();
		names.add("zhang");
		names.add("wang");
		names.add("li");
		names.add("zhao");
        // （1）通过索引循环访问。
		for(int i = 0 ; i < names.size();i++){
			String name = names.get(i);
			name = name.toUpperCase();
			names.set(i, name);
		}
		System.out.println(names);
		//（2）使用迭代器。
		Iterator<String> it = names.iterator();
		ArrayList<String> names2 = new ArrayList<>();
		while(it.hasNext()){
			String name = (String)it.next();
			name = name.toUpperCase();
			it.remove();
			names2.add(name);
		}
		names.addAll(names2);
		System.out.println(names);	
	}
}

