package com.boda.xy;
public class Card {	
	private final Suit suit;
	private final Rank rank;
	public Card(Rank rank, Suit suit) {
		this.rank = rank;
		this.suit = suit;
	}
	public Suit getSuit() {
		return suit;
	}
	public Rank getRank() {
		return rank;
	}
	public String toString() {
		String s = switch(suit) {
		case DIAMONDS -> "♦"; 
		case CLUBS -> "♣";
		case HEARTS -> "♥";
		case SPADES -> "♠";	  
		};
		String r = switch(rank) {
		  case ACE -> "A"; 
		  case JACK -> "J";
		  case QUEEN -> "Q";
		  case KING -> "K";
		  default -> ""+(rank.ordinal()+1);
		};	
		return s + r;
	}
}
