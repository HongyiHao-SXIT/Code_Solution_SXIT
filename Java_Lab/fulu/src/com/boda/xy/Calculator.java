package com.boda.xy;
import javax.swing.*;
import java.awt.*; 
import java.awt.event.*; 
import java.awt.Color;  
public class Calculator extends JFrame implements ActionListener{ 
    private JPanel p1 = new JPanel(); //创建面板 
    private JPanel p2 = new JPanel(); 
    private JTextField t1; //文本框1用来显示输入信息 
    private JTextField t2; //文本框2用来显示结果信息 

    StringBuilder str; //显示屏所显示的字符串 
    double x,y;  //x和y都是运算数 
    int z;  //Z表示单击了那一个运算符.0表示"+",1表示"-",2表示"*",3表示"/" 
    private JButton b[] = new JButton[12]; //创建一个有12个按钮的数组 
    private JButton b1,b2,b3,b4,b5,b6,b7,b8; //算术功能按钮 
   
    public Calculator(){ 
       super("计算器");  
       t1 = new JTextField(50); 
       t1.setEditable(false); //只能显示，不能编辑 
       t2 = new JTextField(50); 
       t2.setEditable(false); //只能显示，不能编辑 
   
       //创建一个空字符串缓冲区 
       str=new StringBuilder(); 
  
       p2.setLayout(new GridLayout(3,1,5,0)); //把面扳布局为2行1列 
       p2.add(t2); //添加文本框到面板 
       p2.add(t1); //添加文本框到面板 
       
  
       for(int i=0;i<10;i++) { //分别为数组中0~9号的按钮设置标签，并注册监听器 
         String s=""+i; 
         b[i]= new JButton(s); 
         b[i].addActionListener(this); 
      } 
  
      //实例化各个按钮 
      b[10]= new JButton("-/+"); b[11]= new JButton("."); 
      b1= new JButton("/"); b2= new JButton("←"); 
      b3= new JButton("*"); b4= new JButton("C"); 
      b5= new JButton("+"); b6= new JButton("√"); 
      b7= new JButton("-"); b8= new JButton("="); 
  
      //设置按钮前景色 
      for(int i=0;i<12;i++){ 
         b[i].setForeground(Color.blue); 
      } 
  
      b1.setForeground(Color.red); b3.setForeground(Color.red); 
      b5.setForeground(Color.red); b7.setForeground(Color.red); 
      b2.setForeground(Color.blue); b4.setForeground(Color.blue); 
      b6.setForeground(Color.red); b8.setForeground(Color.blue); 
  
  
      //添加到面板 
      p1.setLayout(new GridLayout(4,5,5,5)); 
      p1.add(b[7]); p1.add(b[8]); p1.add(b[9]); p1.add(b1); p1.add(b2); 
      p1.add(b[4]); p1.add(b[5]); p1.add(b[6]); p1.add(b3); p1.add(b4); 
      p1.add(b[1]); p1.add(b[2]); p1.add(b[3]); p1.add(b5); p1.add(b6); 
      p1.add(b[0]); p1.add(b[10]); p1.add(b[11]);p1.add(b7);p1.add(b8); 
      
  
  
      //注册监听器 
      b[10].addActionListener(this); b[11].addActionListener(this); 
      b1.addActionListener(this); b2.addActionListener(this); 
      b3.addActionListener(this); b4.addActionListener(this); 
      b5.addActionListener(this); b6.addActionListener(this); 
      b7.addActionListener(this); b8.addActionListener(this); 

      
      add(p2,BorderLayout.PAGE_START);  //使用默认边界布局
      add(p1,BorderLayout.CENTER); 
      
      setSize(340,220); //设置窗口大小
      setResizable(false); //禁止调整框架大小 
      setLocationRelativeTo(null);
      setVisible(true); //设置为可见 
      setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE); //设置窗口关闭动作  
    } 

   
    //按钮的事件处理 
    public void actionPerformed(ActionEvent e){ 
       try{ 
         if(e.getSource()==b4) { //选择"C"清零             
           t1.setText("0"); //把文本框清零 
           t1.setHorizontalAlignment(JTextField.RIGHT); //文本对齐右边 
           str.setLength(0); //清空字符串缓冲区以准备接收新的输入运算数 
           t2.setText(""); //把文本框清空
           t2.setHorizontalAlignment(JTextField.RIGHT); 
         } else if(e.getSource()==b[10]) {//单击"+/-"选择输入的运算数是正数还是负数 
            x=Double.parseDouble(t1.getText().trim()); 
            t1.setText(""+(-x)); 
            t1.setHorizontalAlignment(JTextField.RIGHT); 
         } else if (e.getSource()==b5){//单击加号按钮获得x的值和z的值并清空y的值 
           x=Double.parseDouble(t1.getText().trim()); 
           str.setLength(0); 
           y=0d; 
           z=0; 
         }else if(e.getSource()==b7){//单击减号按钮获得x的值和z的值并清空y的值  
           x=Double.parseDouble(t1.getText().trim()); 
           str.setLength(0); 
           y=0d; 
           z=1; 
         }else if(e.getSource()==b3){//单击乘号按钮获得x的值和z的值并清空y的值 
            x=Double.parseDouble(t1.getText().trim()); 
            str.setLength(0); 
            y=0d; 
            z=2; 
         }else if(e.getSource()==b1){//单击除号按钮获得x的值和z的值并清空y的值 
            x=Double.parseDouble(t1.getText().trim()); 
            str.setLength(0); 
            y=0d; 
            z=3; 
         }else if(e.getSource()==b8){//单击等号按钮输出计算结果 
            str.setLength(0); 
            switch(z){ 
              case 0 -> {t2.setText(""+(x+y)); 
                         t2.setHorizontalAlignment(JTextField.RIGHT); 
              }
              case 1-> {
            	  t2.setText(""+(x-y)); 
            	  t2.setHorizontalAlignment(JTextField.RIGHT); 
              }
              case 2->{
            	  t2.setText(""+(x*y)); 
            	  t2.setHorizontalAlignment(JTextField.RIGHT); 
              }
              case 3-> {
            	  t2.setText(""+(x/y)); 
            	  t2.setHorizontalAlignment(JTextField.RIGHT); 
              }
           } 
        }else if(e.getSource()==b[11]){//单击"."按钮输入小数  
          if(t1.getText().trim().indexOf('.')!=-1)//判断字符串中是否已经包含了小数点 
          { 
          }else{ //如果没有小数点 
            if(t1.getText().trim().equals("0"))//如果初时显示为0 
            { 
            t1.setText(str.append(e.getActionCommand()).toString()); 
            t1.setHorizontalAlignment(JTextField.RIGHT); 
            } 
           else if(t1.getText().trim().equals(""))//如果初时显示为空则不做任何操作 
           {} 
           else{ 
             t1.setText(str.append(e.getActionCommand()).toString()); 
             t1.setHorizontalAlignment(JTextField.RIGHT); 
           }  
         } 
         y=0d; 
       }else if(e.getSource()==b6) { //求平方根 
          x=Double.parseDouble(t1.getText().trim()); 
          if(x < 0){ 
             t1.setText("数字格式异常"); 
             t1.setHorizontalAlignment(JTextField.RIGHT); 
          }else{ 
             t2.setText(""+Math.sqrt(x)); 
             t2.setHorizontalAlignment(JTextField.RIGHT); 
          } 
          str.setLength(0); 
          y=0d; 
       }else{ 
          if(e.getSource()==b[0]){//如果选择的是"0"这个数字键 
            if(t1.getText().trim().equals("0"))//如果显示屏显示的为零不做操作 
            {} 
           else
             t1.setText(str.append(e.getActionCommand()).toString()); 
          t1.setHorizontalAlignment(JTextField.RIGHT); 
          y=Double.parseDouble(t1.getText().trim()); 
      }else if (e.getSource()==b2) { //选择的是back键   
        if(!t1.getText().trim().equals("0"))//如果显示屏显示的不是零 
         { 
        if(str.length()!=1) 
        { 
       t1.setText(str.delete(str.length()-1,str.length()).toString());//可能抛出字符串越界异常 
       t1.setHorizontalAlignment(JTextField.RIGHT); 
       } else{ 
         t1.setText("0"); 
         t1.setHorizontalAlignment(JTextField.RIGHT); 
         str.setLength(0); 
        } 
       } 
        y=Double.parseDouble(t1.getText().trim()); 
       }else { 
         t1.setText(str.append(e.getActionCommand()).toString()); 
         t1.setHorizontalAlignment(JTextField.RIGHT); 
         y=Double.parseDouble(t1.getText().trim()); 
       } 
     } 
   } catch(NumberFormatException e1){ 
	 t1.setText("数字格式异常"); 
     t1.setHorizontalAlignment(JTextField.RIGHT); 
   } catch(StringIndexOutOfBoundsException e1){
	 t1.setText("字符串索引越界"); 
     t1.setHorizontalAlignment(JTextField.RIGHT);} 
   } 
    //主方法实现创建一个窗口 
    public static void main(String[] args) { 
	   try{
	     UIManager.setLookAndFeel(
		      UIManager.getSystemLookAndFeelClassName());
       }catch(Exception e){}     
	   SwingUtilities.invokeLater(new Runnable() {
         public void run() {
              new Calculator();
          }
      });
    }     
}
