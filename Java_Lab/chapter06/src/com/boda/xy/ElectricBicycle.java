package com.boda.xy;
class Vehicle{ 
  public Vehicle(){
    System.out.println("创建Vehicle对象");
  }
}
class Bicycle extends Vehicle{
   private String brand;
   public Bicycle(){
      this("捷安特");
      System.out.println("创建Bicycle对象");
  }
  public Bicycle (String brand){    
     this.brand = brand;
  }
	public String getBrand() {
		return brand;
	}
}
//ElectricBicycle类扩展了Bicycle类
public class ElectricBicycle extends Bicycle{
 String factory;
  public ElectricBicycle(){
      System.out.println("创建ElectricBicycle对象");
  }
  public static void main(String[] args){
      ElectricBicycle myBicycle = new ElectricBicycle();
      System.out.println(myBicycle.getBrand());
  }
}

