package com.boda.xy;
public class RecordDemo {
	private static class PersonInfo {
		private final String name;
		private final int age;
		private final String address;

		PersonInfo(String name, int age, String address) {
			this.name = name;
			this.age = age;
			this.address = address;
		}

		public String name() {
			return name;
		}

		public int age() {
			return age;
		}

		public String address() {
			return address;
		}

		@Override
		public String toString() {
			return "PersonInfo[name=" + name + ", age=" + age + ", address=" + address + "]";
		}

		@Override
		public boolean equals(Object obj) {
			if (this == obj) {
				return true;
			}
			if (!(obj instanceof PersonInfo)) {
				return false;
			}
			PersonInfo other = (PersonInfo) obj;
			return age == other.age && name.equals(other.name) && address.equals(other.address);
		}

		@Override
		public int hashCode() {
			int result = name.hashCode();
			result = 31 * result + age;
			result = 31 * result + address.hashCode();
			return result;
		}
	}

	public static void main(String[] args) {
		var person = new PersonInfo("张明月",20,"北京市海淀区");
		var person2 = new PersonInfo("张明月",20,"北京市海淀区");
		System.out.println("姓名：" + person.name());
		System.out.println("年龄：" + person.age());
		System.out.println("地址：" + person.address());		
		System.out.println(person.toString());
		System.out.println(person.equals(person2));
		System.out.println(person.hashCode());
		System.out.println(person2.hashCode());
	}
}

