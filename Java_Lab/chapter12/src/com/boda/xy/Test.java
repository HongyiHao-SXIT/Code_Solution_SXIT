package com.boda.xy;

import javax.swing.JOptionPane;

public class Test {

	public static void main(String[] args) {
		String a[] = {"a","b"};
		int m = JOptionPane.showConfirmDialog(null,"是否真要删除文件？","选择一个选项",JOptionPane.YES_NO_CANCEL_OPTION);
		System.out.println("数组长度:" + a.length + ", 选择结果:" + m);

	}

}
