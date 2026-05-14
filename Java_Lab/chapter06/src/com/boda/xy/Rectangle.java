package com.boda.xy;
public class Rectangle extends Shape{
    private double height;
    private double width;
    
    public Rectangle(){
       this(0.0,0.0);
    }
    public Rectangle(double height,double width){
       super("矩形");                  // 调用父类的构造方法
       this.height = height;
       this.width = width;
    }
   
    @Override
    public double getPerimeter(){    // 实现父类的抽象方法
        return 2 * (height + width);
    }
    @Override
    public double getArea(){          // 实现父类的抽象方法
        return height * width;
    }
    @Override
    public String toString(){        // 覆盖Object类的toString()方法
       return "[矩形 height = "+ height + ", width =" + width;
    }
}
