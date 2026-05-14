package com.boda.xy;
import java.awt.*;
import javax.swing.*;
public class GridLayoutDemo{     
   public static void main(String[] args) {
      var frame = new JFrame("GridLayoutDemo");
      frame.setLayout(new GridLayout(3,2));
      // 向容器中添加8个按钮
      for(var i = 1; i <=8; i++){
          frame.add(new JButton("Button "+i));
      }
      frame.setSize(300,150);
      frame.setLocationRelativeTo(null);
      frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
      frame.setVisible(true);
  }
}

