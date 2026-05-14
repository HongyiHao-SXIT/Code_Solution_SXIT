package com.boda.xy;
import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.Iterator;
public class BridgeCards {
	public static void main(String[] args) {
	    Card[] cards = new Card[52];
	    ArrayList<Integer> numbers = new ArrayList<Integer>();
	    for(int i = 0; i< 52;i++) {
	    	numbers.add(i);
	    }
	    Collections.shuffle(numbers);
	    
	    HashMap<Integer, Card> decks = new HashMap<Integer, Card>();
	    //创建每张牌并存放到HashMap中
	    int i = 0;
	    for (Suit suit : Suit.values()) {
	        for (Rank rank: Rank.values()) {	        	   
	        	cards[i] = new Card(rank, suit);
	            decks.put(numbers.get(i), cards[i]);
	            i ++;
	        }
	     }
	   // 发牌
	   ArrayList<Card> player1 = new  ArrayList<Card>();
	   ArrayList<Card> player2 = new  ArrayList<Card>();
	   ArrayList<Card> player3 = new  ArrayList<Card>();
	   ArrayList<Card> player4 = new  ArrayList<Card>();
	   Iterator<Integer> it = decks.keySet().iterator();
	   while(it.hasNext()) {
		   player1.add(decks.get(it.next()));
		   player2.add(decks.get(it.next()));
		   player3.add(decks.get(it.next()));
		   player4.add(decks.get(it.next()));
	   }
	   //输出每个玩家得到的牌
	   System.out.println("玩家1 "+player1);
	   System.out.println("玩家2 "+player2);
	   System.out.println("玩家3 "+player3);
	   System.out.println("玩家4 "+player4);		   
   }
}
