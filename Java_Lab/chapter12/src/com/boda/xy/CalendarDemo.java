package com.boda.xy;
import java.awt.*;
import java.awt.event.*;
import javax.swing.*;
import java.time.LocalDate;

public class CalendarDemo extends JFrame implements ItemListener {
	JPanel top = new JPanel();
	JPanel center = new JPanel();
	JComboBox<String> year = new JComboBox<>(); 
	JComboBox<String> month = new JComboBox<>();
	JLabel [][]day = new JLabel[6][7];  
	public CalendarDemo() {
		super("简易日历");
		for(var i = 0; i<6;i++) {
			for(var j =0;j<7;j++) {
				day[i][j] = new JLabel("",SwingConstants.CENTER);
			}
		}
		
		for(int y = 2010;y<2031;y++) {
			year.addItem(y+"年");
		}
		for(int m=1;m<=12;m++) {
			month.addItem((m<10?("0"+m):m)+"月");
		}
        top.setLayout(new FlowLayout(FlowLayout.LEFT));
		top.add(year);
		top.add(month);
		center.setLayout(new GridLayout(0,7,1,1));
		
		String []w = {"一","二","三" ,"四","五","六","日"};
        for(var i = 0;i<7;i++) {
        	center.add(new JLabel(w[i],SwingConstants.CENTER));
        }
		
		var dates = LocalDate.of(LocalDate.now().getYear(),LocalDate.now().getMonthValue(),1);
		var daysOfMonth = dates.lengthOfMonth();   //月天数
		var dayOfWeek = dates.getDayOfWeek().getValue();  // 第1天周几
		int n = 1;		
		for(int i=0;i<6;i++) {
			for(int j=0;j<7;j++) {
				center.add(day[i][j]);
				if(i==0&&j<=dayOfWeek-2) {
					day[i][j].setText("");					
				}else {
					if(n<=daysOfMonth) {
						day[i][j].setText(n+"");						
						n++;
					}
				}			
			}		
		}
		
		add(top, BorderLayout.PAGE_START);
		add(center, BorderLayout.CENTER);
        
		year.addItemListener(this);
		month.addItemListener(this);
		
		setSize(450, 300);
		setLocationRelativeTo(null);
		setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
		setVisible(true);
	}

	public void itemStateChanged(ItemEvent e) {
		int y = Integer.parseInt(((String)(year.getSelectedItem())).substring(0,4));
		int m = Integer.parseInt(((String)(month.getSelectedItem())).substring(0,2));
		
		var dates = LocalDate.of(y,m,1);
		var daysOfMonth = dates.lengthOfMonth();   //月天数
		var dayOfWeek = dates.getDayOfWeek().getValue();  // 第1天周几
		
		int n = 1;		
		for(int i=0;i<6;i++) {
			for(int j=0;j<7;j++) {
				day[i][j].setText("");
				if(i==0 && j <= dayOfWeek-2) {
					// 前面的标签步显示内容					
				}else {
					// 修改标签上显示的日期
					if(n<=daysOfMonth) {
						day[i][j].setText(n+"");						
						n++;
					}
				}
			}		
		}
	}
	
	public static void main(String[] args) {
		try {
			UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
		} catch (Exception e) {
		}	
		SwingUtilities.invokeLater(new Runnable() {
			public void run() {
				new CalendarDemo();
			}
		});
	}
}

