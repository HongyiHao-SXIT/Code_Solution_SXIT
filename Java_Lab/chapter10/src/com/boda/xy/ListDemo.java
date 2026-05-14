package com.boda.xy;
import java.util.*;
public class ListDemo{
    public static void main(String[] args){
       var myPets = new ArrayList<String>();
myPets.add("猫");
myPets.add("狗");
myPets.add("马");
for(var pet: myPets){
    System.out.print(pet + "  ");
}
System.out.println();
var bigPets = new String[]{"老虎","狮子"};
List<String> list = new ArrayList<> ();
list.add(bigPets[0]);
list.add(bigPets[1]);
myPets.addAll(list);
System.out.println(myPets.contains("大象"));
	    var iterator = myPets.iterator();
	    while(iterator.hasNext()){
    String pet = iterator.next();
    System.out.print(pet + "  ");
}
   }
}
