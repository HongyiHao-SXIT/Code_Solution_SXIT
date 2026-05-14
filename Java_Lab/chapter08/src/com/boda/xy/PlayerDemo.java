package com.boda.xy;
public class PlayerDemo {
	public static void main(String[] args) {
	    Player player;
	    MusicPlayer mplayer = new MusicPlayer();
	    mplayer.play();
	    player = mplayer;
	    player.stop();
	    MusicPlayer mplayer2 = (MusicPlayer)player;
	    mplayer2.encodeAudio();
	}
}
