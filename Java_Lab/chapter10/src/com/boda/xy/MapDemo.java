package com.boda.xy;
import java.util.*;
public class MapDemo {
   public static void main(String[] args) {
      var country = new String[]{"中国","印度","澳大利亚",
                      "德国","古巴","希腊","日本"};
      var capital = new String[]{"北京","新德里","堪培拉","柏林",
                      "哈瓦那","雅典","东京"};
      var m = new HashMap<>();
      for(var i = 0;i<country.length;i++){
          m.put(country[i], capital[i]);
      }
      System.out.println("共有 " + m.size() + " 个国家:");
      System.out.println(m);
      System.out.println(m.get("中国"));
      m.remove("日本");   
      var coun = m.keySet();
      for(var c : coun)
         System.out.print(c + " ");	
     }
}
