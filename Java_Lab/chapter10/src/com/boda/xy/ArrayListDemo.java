package com.boda.xy;

import java.util.ArrayList;

public class ArrayListDemo {

	public static void main(String[] args) {
		var cities = new ArrayList<>();
		cities.add("北京");
		cities.add("上海");
		cities.add("广州");
		System.out.println(cities.size());
		cities.add(2,"伦敦");                             // 插入元素
		cities.set(2,"纽约");                             // 修改元素
		System.out.println(cities.contains("北京"));
		System.out.println(cities);
		System.out.println(cities.indexOf("巴黎"));


	}

}
