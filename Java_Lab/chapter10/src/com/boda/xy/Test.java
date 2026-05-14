package com.boda.xy;

public class Test{
    	public static void methodA() { 
            try{
        var value = 30;
        if(value < 40)
          throw new Exception("value值太小。");
} catch(Exception ex){
         System.out.println(ex.getMessage());
}
System.out.println("catch块后的代码。");

    		 } 
}
