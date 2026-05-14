package com.boda.xy;
import java.time.DayOfWeek;
public class EnumSwitch{
    public static void describe (DayOfWeek day) {
       switch (day) {
	        case MONDAY -> 
            System.out.println("Mondays are bad.");
	        case FRIDAY -> 
              System.out.println("Fridays are better.");
         case SATURDAY,SUNDAY -> 
              System.out.println("Weekends are best.");
	       default ->
             System.out.println("Midweek days are so-so.");
       }
      }
	
     public static void main(String[] args) {
    	   var firstDay = DayOfWeek.MONDAY;
         describe (firstDay);
         var thirdDay = DayOfWeek.WEDNESDAY;
         describe (thirdDay);
         var seventhDay = DayOfWeek.SUNDAY;
         describe(seventhDay); 
    }
}

