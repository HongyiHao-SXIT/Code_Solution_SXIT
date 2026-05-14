package com.boda.xy;
import java.util.Arrays;
public class BridgeCards {
	  public static void main(String[]args){
	      int[]deck = new int[52];
	      String[] suits = {"♠","♥","♦","♣"};
	      String[] ranks = {"A","K","Q","J","10","9","8","7",
	                            "6","5","4","3","2"};
	      //初始化每一张牌
	      for(var i = 0; i < deck.length;i++)
	          deck[i] = i;
	      // 打乱牌的次序
	      for(var i = 0; i < deck.length;i++){
	          // 随机产生一个元素下标0~51
	          int index = (int)(Math.random()*deck.length);
	          int temp = deck[i]; // 将当前元素与产生的元素交换
	          deck[i] = deck[index];
	          deck[index] = temp;
	      }
	  	  // 对指定范围的数组元素排序
	      Arrays.sort(deck,0,13);
	      Arrays.sort(deck,13,26);
	      Arrays.sort(deck,26,39);
	      Arrays.sort(deck,39,52);
	      // 显示所有52张牌
	      for(var i = 0; i < 52; i++){
		      switch(i%13) {
		         case 0-> System.out.print("玩家"+(i/13 +1) +":");
		      }
	 		  String suit = suits[deck[i]/13];     // 确定花色
	          String rank = ranks[deck[i]%13];     // 确定次序
	          System.out.printf("%s%-3s",suit,rank);
	          if((i+1)%13 == 0) {
			   	    System.out.println(); 
		       }
	       }
	   }
}

