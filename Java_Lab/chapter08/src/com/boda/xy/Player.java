package com.boda.xy;
public abstract class Player {
    public String fileName;
    public Player() {
    	System.out.println("创建Player对象。");
    }
    public abstract void play();
    public abstract void stop();
}
