package com.boda.xy;
import java.util.Arrays;
import java.util.Comparator;
public class LengthComparator implements Comparator<String>{ 
    @Override
public int compare(String first, String second){
        return first.length()-second.length();
    }

    public static void main(String[]args){
       var ss = new String[]{"this", "is","a","test","string"};
Arrays.sort(ss,new LengthComparator());    // 对数组ss按长度排序
for(var s : ss)
           System.out.print(s + " ");
}
}

