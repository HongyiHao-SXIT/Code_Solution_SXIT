package com.boda.xy;

import java.io.*;

public class FileDemo {
	public static void main(String[] args) {
		try {
			boolean success = false;
			var file = new File("Hello.txt"); // 此时文件还不存在！
			System.out.println(file.exists()); // 输出false
			success = file.createNewFile(); // 创建文件是否成功
			System.out.println(success);
			System.out.println(file.exists()); // 输出文件是否存在
		} catch (IOException e) {
			System.out.println(e.toString());
		}
	}
}
