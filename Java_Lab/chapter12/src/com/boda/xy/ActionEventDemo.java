package com.boda.xy;
import java.awt.*;
import javax.swing.*;
public class ActionEventDemo{
    JLabel jLabel= new JLabel("请单击按钮.",SwingConstants.CENTER);
	JButton btn1= new JButton("确定"),
		    btn2= new JButton("取消");
	JPanel  jp = new JPanel();   
    
	public ActionEventDemo(){
	   var frame = new JFrame("动作事件");
       
       frame.add(jLabel,BorderLayout.CENTER);
       jp.add(btn1);
       jp.add(btn2);
       frame.add(jp,BorderLayout.PAGE_END);
       
       btn1.addActionListener((e)->jLabel.setText("你单击了'确定'按钮"));
       btn2.addActionListener((e)->jLabel.setText("你单击了'取消'按钮"));
       
       frame.setSize(300,100);
       frame.setLocationRelativeTo(null);
       frame.setVisible(true);
       frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
    }
    // 定义内部类，实现ActionListener接口
       
    public static void main(String[]args){
      SwingUtilities.invokeLater(new Runnable() {
          public void run() {
               new ActionEventDemo();
           }
      });
   }
}

