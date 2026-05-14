package com.boda.xy;
import java.util.Comparator;
public class AgeComparator implements Comparator<Employee> {
	@Override
	public int compare(Employee first, Employee second){
		if(first.getBirthday().isBefore(second.getBirthday())) {
			return -1;
		}else if(second.getBirthday().isBefore(first.getBirthday())) {
			return 1;
		}else {
	        return 0;
	    }
	}
}
