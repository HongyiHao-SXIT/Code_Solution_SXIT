package com.boda.xy;

public class Point<T> {
   private T x;
   private T y;
   
   public Point(T x, T y) {
	   this.x = x;
	   this.y = y;
   }
   public T getX() {
		return x;
	}
   public void setX(T x) {
		this.x = x;
   }
   public T getY() {
		return y;
   }
   public void setY(T y) {
		this.y = y;
   }
   public Point<T> translate(T newX, T newY){
	   Point<T> point = new Point<T>(newX, newY);
	   return point;
   }
   
   @Override
   public String toString() {
	  return "Point [x=" + x + ", y=" + y + "]";
   }
   public static void main(String[] args) {
	   Point<Integer> point = new Point<Integer>(20,30);
	   System.out.println(point);
	   Point<Integer> point2 = point.translate(50, 60);
	   System.out.println(point2);
   }
}