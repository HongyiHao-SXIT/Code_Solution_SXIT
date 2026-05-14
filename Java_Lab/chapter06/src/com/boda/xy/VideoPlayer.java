package com.boda.xy;
public class VideoPlayer extends Player {
    private double duration;
   
    public VideoPlayer() {
	   this("铁血丹心",5);
	   System.out.println("创建VideoPlayer对象。");
    }
    public VideoPlayer(String fileName, double duration) {
	   this.fileName = fileName;
	   this.duration = duration;
    }
    public double getDuration() {
	   return duration;
    }
    public void play() {
	   System.out.println("播放：" + fileName);
    }
    public void stop() {
	   System.out.println("停止播放：" + fileName);
    }
    public void encodeVideo() {
	   System.out.println("解码视频文件。");  
    }
}

