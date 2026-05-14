package com.boda.xy;
public class Student implements Comparable<Student>{
	private int sno;
	private String name;
	private int chineseScore;
	private int mathScore;
	private int englishScore;
	private int totalScore;
	
	public Student(int sno, String name, int chineseScore, int mathScore, int englishScore) {
		this.sno = sno;
		this.name = name;
		this.chineseScore = chineseScore;
		this.mathScore = mathScore;
		this.englishScore = englishScore;
		this.totalScore=chineseScore+mathScore+englishScore;
	}
	public Student() {
		super();
	}
	public String getName() {
		return name;
	}
	public void setName(String name) {
		this.name = name;
	}
	public int getChineseScore() {
		return chineseScore;
	}
	public void setChineseScore(int chineseScore) {
		this.chineseScore = chineseScore;
	}
	public int getMathScore() {
		return mathScore;
	}
	public void setMathScore(int mathScore) {
		this.mathScore = mathScore;
	}
	public int getEnglishScore() {
		return englishScore;
	}
	public void setEnglishScore(int englishScore) {
		this.englishScore = englishScore;
	}
	public int getSno() {
		return sno;
	}	
	public int getTotalScore() {
		return totalScore;
	}
	
	@Override
	public int hashCode() {
		final int prime = 31;
		int result = 1;
		result = prime * result + sno;
		return result;
	}
	@Override
	public String toString() {
		return "学号：" + sno + ", 姓名：" + name 
+ ", 语文成绩：" + chineseScore + ", 数学成绩：" + mathScore
				+ ", 英语成绩：" + englishScore + ", 总成绩：" + totalScore;
	}
	@Override
	public boolean equals(Object obj) {
		if (this == obj)
			return true;
		if (obj == null)
			return false;
		if (getClass() != obj.getClass())
			return false;
		Student other = (Student) obj;
		if (sno != other.sno)
			return false;
		return true;
	}
	
	@Override 
	public int compareTo(Student o) {
		if(!(o instanceof Student))
			return -1;
		Student p=(Student)o;
		return p.totalScore - this.totalScore;
	}	
}

