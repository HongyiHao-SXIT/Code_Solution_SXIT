package com.boda.xy;

public class MismatchedArrayException extends Exception{
	int x =0, y = 0;
	public MismatchedArrayException(int x, int y) {
		this.x = x;
		this.y = y;
	}
	@Override
	public String toString() {
		return "数组大小不同。第一个数组大小是" + x +",第二个数组大小是" + y+".";	
	}
}
