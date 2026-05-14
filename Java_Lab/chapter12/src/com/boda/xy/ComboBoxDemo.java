package com.boda.xy;
import java.awt.*;
import java.awt.event.*;
import java.time.LocalDate;
import javax.swing.*;

public class ComboBoxDemo extends JFrame implements ItemListener {
	JPanel panel = new JPanel();
	JComboBox<String> leave = new JComboBox<>(), // 这里使用了泛型语法
			arrive = new JComboBox<>();
	JTextField leavedate = new JTextField(LocalDate.now().toString()); 
	JTextArea ta = new JTextArea(4, 20);
	JScrollPane jsp = new JScrollPane(ta);

	public ComboBoxDemo() {
		super("组合框案例");
		leave.addItem("请选择出发地");
		leave.addItem("北京");
		leave.addItem("上海");
		leave.addItem("广州");
		arrive.addItem("请选择到达地");
		arrive.addItem("南京");
		arrive.addItem("武汉");
		arrive.addItem("海口");
		
		panel.add(leave);
		panel.add(arrive);
		panel.add(leavedate);
		add(panel, BorderLayout.PAGE_START);
		add(jsp, BorderLayout.CENTER);

		leave.addItemListener(this);
		arrive.addItemListener(this);
		
		setSize(350, 180);
		setLocationRelativeTo(null);
		setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
		setVisible(true);
	}

	public void itemStateChanged(ItemEvent e) {
		String s1 = "出发地:", s2 = "到达地:", s3 = "出发日期:";
		s1 = s1 + leave.getSelectedItem() + "\n";
		s2 = s2 + arrive.getSelectedItem() + "\n";
		s3 = s3 + leavedate.getText();
		ta.setText(s1 + s2 + s3);
	}

	public static void main(String[] args) {
		SwingUtilities.invokeLater(new Runnable() {
			public void run() {
				new ComboBoxDemo();
			}
		});
	}
}
