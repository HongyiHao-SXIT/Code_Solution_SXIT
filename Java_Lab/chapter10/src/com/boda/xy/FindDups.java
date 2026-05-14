package com.boda.xy;
import java.util.*;
public class FindDups {
	public static void main(String[] args) {
		Set<String> uniques = new HashSet<>();
		Set<String> dups = new HashSet<>();
		String message = "i came i saw i left";
		String[] words = message.split(" ");
		for (String a : words)
			if (!uniques.add(a))
				dups.add(a);
		System.out.println("不同单词的个数：" + uniques.size());
		// 去掉重复的单词
		uniques.removeAll(dups);
		System.out.println("不重复的单词：" + uniques);
		System.out.println("重复的单词：" + dups);
	}
}
