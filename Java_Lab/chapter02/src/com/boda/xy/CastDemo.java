package com.boda.xy;

public class CastDemo {

	public static void main(String[] args) {
		System.out.println(Math.PI);   // 输出PI
        double pi = Math.PI;
        pi = pi * 10000 + 0.5;       // 保证四舍五入
        pi = (int) pi;                 // 保留整数部分
        pi = pi / 10000;               // 得到结果
        System.out.println(pi);


	}

}
