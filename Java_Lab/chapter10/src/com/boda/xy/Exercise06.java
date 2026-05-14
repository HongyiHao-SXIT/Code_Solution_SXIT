package com.boda.xy;
import java.util.HashMap;
import java.util.Map;
import java.util.TreeMap;
public class Exercise06 {
	public static void main(String[] args) {
		String[] lines = { "no pains,no gains.", "well begun is half done.", "where there is a will,there is a way." };
		String words[];
		Map<String, Integer> wordMap = new HashMap<>();
		for (int i = 0; i < lines.length; i++) {
			words = lines[i].split("[ ,.]");
			for (String s : words) {
				Integer n = wordMap.get(s);
				if (n == null) {
					wordMap.put(s, 1);
				} else {
					wordMap.put(s, n + 1);
				}
			}
		}
		System.out.println("共有" +wordMap.size()+  "个不同的单词。" );
		System.out.println(wordMap);
		
        Map<String, Integer> wordMap2 = new TreeMap<>(wordMap);
        System.out.println(wordMap2);
	}
}
