package com.boda.xy;
class Animal{
    String name = "大熊猫";
    void display(){
       System.out.println("My name is "+name);
    }
}
public class AnimalTest{
    public static void main(String[] args){
       var a = new Animal();
       System.out.println("a.name = "+a.name);
       a.display();
   }
}
