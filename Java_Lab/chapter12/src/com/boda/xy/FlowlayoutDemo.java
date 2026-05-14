package com.boda.xy;
import java.awt.*;
import javax.swing.*;
public class FlowlayoutDemo{     
   public static void main(String[] args) {
      var frame = new JFrame("FlowLayoutDemo");
      frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
      // 创建一个FlowLayout对象
      var layout = new FlowLayout(FlowLayout.CENTER,10,20);
      frame.setLayout(layout);  // 设置容器的布局管理器
      frame.add(new JButton("Button 1"));
      frame.add(new JButton("2"));
      frame.add(new JButton("Button 3"));
      frame.add(new JButton("Long-Named Button 4"));
      frame.add(new JButton("Button 5"));
      frame.setSize(300,150);
      frame.setLocationRelativeTo(null);
      frame.setVisible(true);
  }
}

