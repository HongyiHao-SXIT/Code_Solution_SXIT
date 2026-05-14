package com.boda.xy;
import java.awt.event.*;
import javax.swing.*;

public class MenuDemo extends JFrame implements ActionListener{
	private JMenuBar jmb;
	private JMenu fileMenu, editMenu, helpMenu;
	private JMenu fontMenu, colorMenu;
	private JMenuItem jmiNew, jmiOpen, jmiAbout;

	public MenuDemo() {
		setTitle("菜单案例");
		jmb = new JMenuBar();
		setJMenuBar(jmb); // 将菜单栏添加到窗口上
		fileMenu = new JMenu("文件(F)");
		fileMenu.setMnemonic('F');  // 设置热键
		editMenu = new JMenu("编辑");
		helpMenu = new JMenu("帮助(H)");
		helpMenu.setMnemonic('H');
		
		jmb.add(fileMenu); // 将菜单添加到菜单条中
		jmb.add(editMenu);
		jmb.add(helpMenu);
		
		jmiNew = new JMenuItem("新建");
		jmiNew.setMnemonic('N'); // 设置热键
		jmiNew.setIcon(new ImageIcon("image/new.gif"));
		jmiOpen = new JMenuItem("打开");
		jmiOpen.setMnemonic('O');
		jmiOpen.setIcon(new ImageIcon("image/open.gif"));
		
		// 为菜单项设置快捷键
		jmiOpen.setAccelerator(KeyStroke.getKeyStroke(KeyEvent.VK_O, ActionEvent.CTRL_MASK));
		fileMenu.add(jmiNew);
		fileMenu.add(jmiOpen);
		fileMenu.addSeparator();
		fileMenu.add(new JMenuItem("打印"));
		fileMenu.addSeparator();
		fileMenu.add(new JMenuItem("退出"));
		
		fontMenu = new JMenu("字体");
		editMenu.add(fontMenu);
		fontMenu.add(new JMenuItem("正常"));
		fontMenu.add(new JMenuItem("粗体"));
		fontMenu.add(new JMenuItem("斜体"));
		editMenu.add(new JCheckBoxMenuItem("格式化"));
		colorMenu = new JMenu("颜色");
		editMenu.add(colorMenu);
		
   	    helpMenu.add(jmiAbout = new JMenuItem("关于..."));
		jmiAbout.addActionListener(this);
		
		JPopupMenu popupMenu = new JPopupMenu();
		JMenuItem item1 = new JMenuItem("查看");
		JMenuItem item2 = new JMenuItem("刷新");
		
		popupMenu.add(item1);
		popupMenu.addSeparator();
		popupMenu.add(item2);

		addMouseListener(new MouseAdapter() {
			public void mouseClicked(MouseEvent e) {
			     if(e.getButton()==MouseEvent.BUTTON3) {
			    	 popupMenu.show(e.getComponent(),e.getX(),e.getY());
			     }
			}
		});
		setSize(300, 200);
		setVisible(true);
	    setLocationRelativeTo(null);
		setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
	}

	public void actionPerformed(ActionEvent e) {
		String m = e.getActionCommand();
		if (m.equals("About")) {
			JOptionPane.showMessageDialog(MenuDemo.this, "菜单案例");
		}
	}

	public static void main(String[] args) {
		new MenuDemo();
	}
}
