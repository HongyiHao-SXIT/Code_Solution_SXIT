package com.boda.xy;
public class Triangle{
    public static void main(String[] args){
      var level = 10;
      var triangle = new int[level][];
      for(var i = 0;i < triangle.length;i++)
        triangle[i] = new int[i+1];
      // 为triangle数组的每个元素赋值
      triangle [0][0] = 1;
      for(var i = 1;i < triangle.length;i++){
        triangle[i][0] = 1;
        for(var j = 1; j < triangle[i].length-1; j++)
          triangle[i][j] = triangle[i-1][j-1]+ triangle[i-1][j];
        triangle[i][triangle[i].length-1] = 1;
      }
      // 打印输出triangle数组的每个元素
      for(var i = 0;i < triangle.length; i++){
         for(var j = 0;j < triangle[i].length;j++)
            System.out.print(triangle[i][j]+" ");
         System.out.println();  // 换行
      }
    }
}

