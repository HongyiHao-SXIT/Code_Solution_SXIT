package com.boda.xy;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.Scanner;

public class FileDecription {
	public static void main(String[] args) throws IOException{
	   Scanner input = new Scanner(System.in);
	   String secretFile = null;
	   String resultFile = null;
	   var keyValue = 0;
	   System.out.print("请输入要解密文件: ");
	   secretFile = input.nextLine();
	   System.out.print("请输入解密后文件名: ");
	   resultFile = input.nextLine();
	   System.out.print("请输入秘钥： ");
	   keyValue = input.nextInt();
		   
	   File encFile = new File(secretFile);
	   File decFile = new File(resultFile);
		   
	   if(!encFile.exists()) {
		   System.out.println("解密的文件不存在！ ");
		   System.exit(0);
	   }
		   
	   if(!decFile.exists()) {
		   System.out.println("创建目标文件！ ");
		   decFile.createNewFile();
	   }
	   try(
		  InputStream fis = new FileInputStream(encFile);
		  OutputStream fos = new FileOutputStream(decFile);	   
	   ) {
		   int dataOfFile = fis.read();
		   while(dataOfFile !=-1) {
			   dataOfFile = dataOfFile^keyValue;
			   fos.write(dataOfFile);
			   dataOfFile = fis.read();
		   }   
	   }
	   System.out.println("文件解密完成！ ");
	   input.close();  
   }
}
