package com.boda.xy;

public class NodeTest {
	public static void main(String[] args) {
		var intNode = new Node<Integer>();
		intNode.setData(999);
		var value = intNode.getData();
		System.out.println(value);
		intNode.showType();
	}
}
