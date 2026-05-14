package com.boda.xy;
public class MusicPlayer extends Player {
   private int volume; 
   public MusicPlayer() {
	   this("铁血丹心",5);
	   System.out.println("创建MusicPlayer对象。");
   }
   public MusicPlayer(String fileName, int volume) {
	   this.fileName = fileName;
	   this.volume = volume;
   }   
   public void play() {
	   System.out.println("开始播放：" + fileName);
   }
   public void stop() {
	   System.out.println("停止播放：" + fileName);
   }
   public void encodeAudio() {
	   System.out.println("解码音频文件。");  
   }
   public int getVolume() {
	   return volume;
   }
}
