package com.boda.xy;

import java.io.*;

public class FileCopyDemo {
	public static void main(String[] args) {
		var inputFile = new File("input.txt");
		var outputFile = new File("output.txt");
		try (var in = new FileReader(inputFile); var out = new FileWriter(outputFile);) {
			int c = in.read();
			while (c != -1) {
				out.write(c);
				c = in.read();
			}
			System.out.println("文件成功复制完成！");
		} catch (IOException e) {
			System.out.println(e.toString());
		}

	}
}
