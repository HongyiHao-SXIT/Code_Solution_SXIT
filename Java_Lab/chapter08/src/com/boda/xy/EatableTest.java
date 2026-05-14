package com.boda.xy;

abstract class Food{}
abstract class Fruit extends Food implements Eatable{}
class Beef extends Food implements Eatable{
public String howToEat(){
    return  "炸牛排";
}
}
class Mutton2 extends Food implements Eatable{
public String howToEat(){
    return  "烤羊肉";
}
}
class Apple extends Fruit{
public String howToEat(){
   return  "制作果酱";
}
}
class Orange extends Fruit{
public String howToEat(){
    return  "榨橘子汁";
 }
}
public class EatableTest{
    public static void main(String args[]){
       Object[] obj = {new Beef(),new Mutton2(),new Apple(),new Orange()};
       for(int i =0 ; i < obj.length;i++){
         if(obj[i] instanceof Eatable)
         System.out.println(((Eatable)(obj[i])).howToEat());
      }
   }
}

