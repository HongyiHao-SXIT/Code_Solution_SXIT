package com.boda.xy;

public class CurrentTime {
    public static void main(String[] args) {
		long t = System.currentTimeMillis();
		long seconds = t / 1000;       // 总秒数
		long s = seconds % 60;
		long minutes = seconds / 60;   //总分钟数
		long m = minutes % 60 ;
		long hours = minutes / 60;
		long h = hours %24;
		System.out.println("当前时间是：" + h +":" +m + ":" + s);
		
		//System.out.println("当前时间是：" + LocalTime.now().truncatedTo(ChronoUnit.SECONDS));
	}
}
