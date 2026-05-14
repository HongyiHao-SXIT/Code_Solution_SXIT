package com.boda.xy;
public class MathUtil {
    public static <T> void swap(T[] array,int i, int j){
     T temp = array[i];
     array[i] = array[j];
     array[j] = temp;
}
public static <K, V> boolean compare(Pair<K, V> p1, Pair<K, V> p2) {
        return p1.getKey().equals(p2.getKey()) &&
               p1.getValue().equals(p2.getValue());
    }
  
public static void main(String[] args) {
var numbers = new Integer[]{1, 3, 5, 7};
MathUtil.swap(numbers, 0, 3);
for(var n:numbers){
   System.out.print(n + "  ");   // 输出7 3 5 1
}
var p1 = new Pair<>(1, "apple");
var p2 = new Pair<>(2, "orange");
//调用泛型方法
boolean same = MathUtil.compare(p1, p2);
        System.out.println("\n" + same);     // 输出false 
   }
}

