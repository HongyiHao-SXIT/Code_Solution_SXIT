package com.boda.xy;
import java.util.Scanner;
import java.io.File;
import java.io.IOException;
import java.io.FileInputStream;
import java.io.FileOutputStream;

public class FileEncription{ 
   public static void main(String[] args) throws IOException{
	   var input = new Scanner(System.in);
	   String sourceFile = null;
	   String secretFile = null;
	   var keyValue = 0;
	   System.out.print("请输入源文件名: ");
	   sourceFile = input.nextLine();
	   System.out.print("请输入加密文件名: ");
	   secretFile = input.nextLine();
	   System.out.print("请输入秘钥： ");
	   keyValue = input.nextInt();
	   
	   var srcFile = new File(sourceFile);
	   var encFile = new File(secretFile);
	   
	   if(!srcFile.exists()) {
		   System.out.println("源文件不存在！ ");
		   System.exit(0);
	   }
	   
	   if(!encFile.exists()) {
		   System.out.println("创建加密文件！ ");
		   encFile.createNewFile();
	   }
	   try(
		  var fis = new FileInputStream(srcFile);
		  var fos = new FileOutputStream(encFile);	   
	   ) {
		   var dataOfFile = fis.read();
		   while(dataOfFile !=-1) {
			   dataOfFile = dataOfFile^keyValue;
			   fos.write(dataOfFile);
			   dataOfFile = fis.read();
		   }   
	   }
	   System.out.println("文件加密完成！ ");
	   input.close();  
    }
}

