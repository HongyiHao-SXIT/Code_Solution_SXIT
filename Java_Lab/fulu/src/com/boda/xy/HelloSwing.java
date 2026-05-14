package com.boda.xy;
import javax.swing.*;

public class HelloSwing {
      public static void main(String[] args) {
           var frame = new JFrame("HelloSwing");
           var label = new JLabel("第一个Swing程序。", SwingConstants.CENTER);
           frame.setSize(300, 100);
           frame.add(label);
           frame.setLocationRelativeTo(null);
           frame.setVisible(true);
           frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
    }
}

