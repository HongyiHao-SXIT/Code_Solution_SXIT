package com.boda.xy;
import java.awt.*;
import javax.swing.*;
public class BorderLayoutDemo{     
   public static void main(String[] args) {
       var frame = new JFrame("BorderLayoutDemo");
       var jButton1 = new JButton("页头");
       var jButton2 = new JButton("页尾");
       var jButton3 = new JButton("行头");
       var jButton4 = new JButton("行尾");
       var jTextField = new JTextField("中央");

       frame.setLayout(new BorderLayout(10,10)); // 设置边界式布局
       frame.add(jButton1, BorderLayout.PAGE_START);
       frame.add(jButton2, BorderLayout.PAGE_END);
       frame.add(jButton3, BorderLayout.LINE_START);
       frame.add(jButton4, BorderLayout.LINE_END);
       frame.add(jTextField, BorderLayout.CENTER);

       frame.setSize(300,150);
       frame.setLocationRelativeTo(null);
       frame.setVisible(true);
       frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
  }
}

