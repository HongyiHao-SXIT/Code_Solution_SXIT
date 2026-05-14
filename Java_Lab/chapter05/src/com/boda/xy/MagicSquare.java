package com.boda.xy;
import java.util.Scanner;
@SuppressWarnings("resource")
public class MagicSquare {
	  public static void main(String[] args) {
		  var input = new Scanner(System.in);
		  int size;
		  System.out.print("请输入魔方矩阵的大小（1-99）:");
		  size = input.nextInt();
		  if(size < 0 && size % 2 == 0){
            System.out.println("你输入的数不是奇数，不能计算魔方数！");
            System.exit(0);
        }
          var magic = new int[size][size];
		  // 用1到size*size值填充每个元素
		  int row = 0, col = size/2;
		  magic[row][col] = 1;  // 将1填到第一行中间元素
		  for(var n = 2; n <= size*size; n++){
            int tempx,tempy;
            tempx = row;
            tempy = col;
            tempx = tempx - 1;
            tempy = tempy + 1;
            
            if(tempx < 0)
              tempx=size-1;
            if(tempy == size)
               tempy = 0;
           
            if(magic[tempx][tempy]!=0){
                magic[row+1][col] = n;
                row++;
            }else if(magic[tempx][tempy]==0) {
                 magic[tempx][tempy]=n;
                 row = tempx;
                 col = tempy;
             }
		  }
       // 输出数组元素
	     for(var i = 0; i < magic.length; i ++){
	    	 for(var j = 0 ;j < magic.length; j ++){
	    		System.out.printf("%4d",magic[i][j]);
	    	 }
	    	 System.out.println();
	     }		
	  }
}
