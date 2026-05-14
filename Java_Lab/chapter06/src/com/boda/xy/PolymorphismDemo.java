package com.boda.xy;

public class PolymorphismDemo {
	// 计算所有形状面积和
	public static double sumArea(Shape[] shapes) {
		double sumArea = 0;
		for (Shape shape : shapes) {
			System.out.println(shape.getArea()); // 计算实际类型的面积
// 根据对象类型调用不同的getArea()方法
			sumArea = sumArea + shape.getArea();
		}
		return sumArea;
	}

	public static void main(String[] args) {
		double d = 0.0;
		Shape[] shapes = new Shape[5];
		for (var i = 0; i < shapes.length; i++) {
			d = Math.random();
			if (d > 0.5) {
				shapes[i] = new Circle(1);
			} else {
				shapes[i] = new Rectangle(1, 2);
			}
		}
		double sumArea = sumArea(shapes);
		System.out.printf("形状的总面积=%.2f", sumArea);
	}
}
