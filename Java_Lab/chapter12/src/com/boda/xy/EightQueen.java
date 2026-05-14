package com.boda.xy;
import java.awt.Color;
import java.awt.GridLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import javax.swing.ImageIcon;
import javax.swing.JButton;
import javax.swing.JFrame;
import javax.swing.JOptionPane;

public class EightQueen extends JFrame implements ActionListener{
	JButton [][]cells = new JButton[8][8];
	boolean [][] ok = new boolean[8][8];
	public EightQueen(String title){
        super(title);
        setLayout(new GridLayout(8,8,0,0));
        for(int i=0;i<8;i++) {
        	for(int j =0;j<8;j++) {
               ok[i][j] = true;
        	}
        }
        for(int i=0;i<8;i++) {
        	for(int j =0;j<8;j++) {
        		cells[i][j] = new JButton();
        		cells[i][j].addActionListener(this);
        		if((i+j)%2==0) {
        			cells[i][j].setBackground(Color.WHITE);	
        		}else {
        			cells[i][j].setBackground(Color.BLACK);	
        		}
        		add(cells[i][j]);		
        	}
        }              
        setSize(350,350);
        setLocationRelativeTo(null);
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setVisible(true);
    }
  
    public void actionPerformed(ActionEvent e){
	   for(int i=0;i<8;i++) {
	      for(int j =0;j<8;j++) {
	        if((JButton)e.getSource()==cells[i][j]) {
	       	   if(ok[i][j]) {
	       	      cells[i][j].setIcon(new ImageIcon("image\\queen.jpg")); 
	       	      ok[i][j] = false;
	       	      for(int r = 0; r<8;r++) {
	       	    	ok[r][j] = false;     //行
	       	      }
	       	      for(int c = 0; c<8;c++) {
	       	    	  ok[i][c] = false;  //列
	       	      }
	       	    		  
	       	      for(int r = i,c=j; r >=0 && c>=0; r--,c--) {
	       	    	ok[r][c] = false;   // 左上
	       	      }
	       	    		  
	       	      for(int r = i,c=j; r < 8 && c < 8; r++,c++ ) {
	       	    	ok[r][c] = false;   // 右下
	       	      }
	       	      
	       	      for(int r = i,c=j; r >=0 && c < 8; r--,c++) {
	       	    	ok[r][c] = false;   // 右上
	       	      }
	       	  	  for(int r = i,c=j; r < 8 && c >=0;r++,c-- ) {
	       	   		ok[r][c] = false;  // 左下
	       	   	  }	       	  			       	    		  
	       	  }else {
	       	  	JOptionPane.showMessageDialog(null, "这里不能放皇后！");
	       	  }	        			 
	       }	
	   }
	 }        		   	
   }
    
   public static void main(String[] args) {
	  new EightQueen("八皇后问题解");
   }	
}
