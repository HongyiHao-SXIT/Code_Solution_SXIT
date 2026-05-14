package com.boda.xy;

import java.awt.*;
import java.awt.event.*;
import javax.swing.*;

public class RadioCheckDemo extends JFrame implements ItemListener {
	JPanel panelCheck = new JPanel();
	JPanel panelRadio = new JPanel();
	JRadioButton jrb1 = new JRadioButton("苹果"), 
			jrb2 = new JRadioButton("橘子"), 
			jrb3 = new JRadioButton("香蕉");
	ButtonGroup btg = new ButtonGroup();
	JCheckBox ck1 = new JCheckBox("文学"), 
			ck2 = new JCheckBox("艺术"), 
			ck3 = new JCheckBox("体育");
	JTextArea ta = new JTextArea(3, 20);
	JScrollPane jsp = new JScrollPane(ta);

	public RadioCheckDemo() {
		super("RadioCheckBox Demo");
		panelCheck.add(ck1);
		panelCheck.add(ck2);
		panelCheck.add(ck3);

		btg.add(jrb1);
		btg.add(jrb2);
		btg.add(jrb3);
		panelRadio.add(jrb1);
		panelRadio.add(jrb2);
		panelRadio.add(jrb3);
		add(panelRadio, BorderLayout.PAGE_START);
		add(panelCheck, BorderLayout.CENTER);
		add(jsp, BorderLayout.PAGE_END);

		ck1.addItemListener(this);
		ck2.addItemListener(this);
		ck3.addItemListener(this);
		jrb1.addItemListener(this);
		jrb2.addItemListener(this);
		jrb3.addItemListener(this);

		setSize(300, 180);
		setLocationRelativeTo(null);
		setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
		setVisible(true);
	}

	public void itemStateChanged(ItemEvent e) {
		var s1 = "你最喜欢的水果是：";
		var s2 = "你的爱好包括：";
		if (jrb1.isSelected())
			s1 = s1 + jrb1.getText() + "\n";
		if (jrb2.isSelected())
			s1 = s1 + jrb2.getText() + "\n";
		if (jrb3.isSelected())
			s1 = s1 + jrb3.getText() + "\n";
		if (ck1.isSelected())
			s2 = s2 + ck1.getText() + "  ";
		if (ck2.isSelected())
			s2 = s2 + ck2.getText() + "  ";
		if (ck3.isSelected())
			s2 = s2 + ck3.getText() + "  ";
		ta.setText(s1 + s2);
	}

	public static void main(String[] args) {
		SwingUtilities.invokeLater(new Runnable() {
			public void run() {
				new RadioCheckDemo();
			}
		});
	}
}
