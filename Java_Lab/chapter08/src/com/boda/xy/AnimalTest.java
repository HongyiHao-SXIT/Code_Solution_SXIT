package com.boda.xy;
class Animal{
	  public void eat(){
    System.out.println("I like eat anything.");
}
}
public class AnimalTest{
   public static void main(String[]args){
	    var dog = new Animal(){      // 继承Animal类
		     @Override
             public void eat(){  
			     System.out.println("I like eat bones.");
		     }
	    };              
	    dog.eat();
   }
}

