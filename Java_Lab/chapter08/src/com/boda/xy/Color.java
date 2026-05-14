package com.boda.xy;
public enum Color {
    RED("红色", 1), GREEN("绿色", 2), WHITE("白色", 3), YELLOW("黄色", 4);
    private String name;
    private int index;
   
    private Color(String name, int index) {
       this.name = name;
       this.index = index;
    }
    // 普通方法
    public static String getName(int index) {
       for (var c : Color.values()) {
           if (c.getIndex() == index) {
               return c.name;
           }
       }
       return null;
   }
   // getter和setter 方法
   public String getName() {
       return name;
   }
   public void setName(String name) {
       this.name = name;
   }
   public int getIndex() {
       return index;
   }
   public void setIndex(int index) {
       this.index = index;
   }
   @Override
   public String toString() {
       return this.index + "_" + this.name;
   }

   public static void main (String[] args) { 
      var c = Color.YELLOW;                // 这将自动调用构造方法
      System.out.println(c.toString());	  // 输出：4-黄色
      System.out.println(c.name());	      // 输出：YELLOW
      System.out.println(c.ordinal());    // 3
   }
}
