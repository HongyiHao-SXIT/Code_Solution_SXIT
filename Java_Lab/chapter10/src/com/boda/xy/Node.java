package com.boda.xy;
public class Node<T> {
   private T data;               // 泛型成员
   public  Node(){}              // 默认构造方法
   public  Node(T data){        // 带参数构造方法
      this.data = data;
   }
public T getData() {          // 访问方法定义
	     return data;
   } 
   public void setData(T data) {     // 修改方法定义
	     this.data = data;
   }
   // 显示类型名
   public void showType(){
	     System.out.println("T的类型是："+ data.getClass().getName());
   }
 }
