package com.boda.xy;
import java.util.*;
public class DescSortDemo{
   public static void main(String[] args){
      var s = new String[]{"China", "England","France","America","Russia",};
      Set<String> ts = new TreeSet<> ();
      for(var i = 0; i < s.length; i ++)
         ts.add(s[i]);
      System.out.println(ts);
      //使用Lambda表达式实现字符串倒序
      ts = new TreeSet<>(new Comparator<String>(){
            public int compare(String a, String b){
                 return b.compareTo(a);
             }
}); 
      // 将数组s中元素添加到TreeSet对象中
      for(int i = 0; i < s.length; i ++)
          ts.add(s[i]);
      System.out.println(ts);
   }
}
