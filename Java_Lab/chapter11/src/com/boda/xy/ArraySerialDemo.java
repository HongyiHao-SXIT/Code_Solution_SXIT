package com.boda.xy;
import java.io.*;
public class ArraySerialDemo {
	 public static void main(String[]args){
	   try{
var numbers = new int[]{1, 2, 3, 4, 5};
var cities = new String []{"北京","上海","广州"};
	     // 序列化
try(
var output = new FileOutputStream("array.dat",true);
		    var oos = new ObjectOutputStream(output);
){
		    oos.writeObject(numbers);    // 将numbers数组写入文件
		    oos.writeObject(cities);     // 将cities数组写入文件
	    }catch(IOException e){
		    e.printStackTrace();
	    }
	    // 反序列化
	    try(
var input = new FileInputStream("array.dat");
		    var ois = new ObjectInputStream(input);
){
	       // 读取数组对象
var newNumbers = (int[])ois.readObject();
		    var newStrings = (String[])ois.readObject();
         for(var n : newNumbers)
		    System.out.print(n + "  ");
		    System.out.println();
         for(var s : newStrings)
		   System.out.print(s + "  ");
	     }
	   }catch(ClassNotFoundException | IOException e){
		   e.printStackTrace();
	   }
   }
}
