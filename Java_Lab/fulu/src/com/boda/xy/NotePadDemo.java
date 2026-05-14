package com.boda.xy;

import java.awt.*;
import java.io.*;
import java.awt.event.*;
import javax.swing.*;

@SuppressWarnings("unused")
public class NotePadDemo extends JFrame implements ActionListener {
	private JMenuItem jmiNew, jmiOpen, jmiSave, jmiSaveAs, jmiExit, jmiAbout, jmiTopic, jmiCut, jmiCopy, jmiPaste,
			jmiUndo, jmiRedo;
	private JTextArea jta = new JTextArea();
	private JLabel jlbStatus = new JLabel();
	private JFileChooser chooser = new JFileChooser();

	public NotePadDemo() {
		setTitle("无标题-记事本");
		JMenuBar mb = new JMenuBar(); // 创建菜单栏对象
		setJMenuBar(mb);
		JMenu fileMenu = new JMenu("文件(F)"), editMenu = new JMenu("编辑(E)"), formatMenu = new JMenu("格式(O)"),
				viewMenu = new JMenu("查看(V)"), helpMenu = new JMenu("帮助(H)");
		fileMenu.setMnemonic('F'); // 设置热键
		mb.add(fileMenu); // 将菜单添加到菜单栏上
		mb.add(editMenu);
		mb.add(formatMenu);
		mb.add(viewMenu);
		mb.add(helpMenu);
		fileMenu.add(jmiNew = new JMenuItem("新建(N)"));
		fileMenu.add(jmiOpen = new JMenuItem("打开(O)"));
		fileMenu.add(jmiSave = new JMenuItem("保存(S)"));
		fileMenu.add(jmiSaveAs = new JMenuItem("另存为(A)..."));
		fileMenu.addSeparator();
		fileMenu.add(jmiExit = new JMenuItem("退出(E)"));

		editMenu.add(jmiCut = new JMenuItem("剪切"));
		editMenu.add(jmiCopy = new JMenuItem("复制"));
		editMenu.add(jmiPaste = new JMenuItem("粘贴"));
		editMenu.addSeparator();
		editMenu.add(jmiUndo = new JMenuItem("撤销添加"));
		editMenu.add(jmiRedo = new JMenuItem("重做"));

		helpMenu.add(jmiTopic = new JMenuItem("帮助主题"));
		helpMenu.addSeparator();
		helpMenu.add(jmiAbout = new JMenuItem("关于记事本"));

		jmiNew.setMnemonic('N');
		jmiOpen.setAccelerator(KeyStroke.getKeyStroke(KeyEvent.VK_O, ActionEvent.CTRL_MASK));
		chooser.setCurrentDirectory(new File("."));
		
		
		jta.setFont(new Font("TimesRoman",Font.PLAIN,16));
		add(new JScrollPane(jta), BorderLayout.CENTER);
		add(jlbStatus, BorderLayout.PAGE_END);

		jmiOpen.addActionListener(this);
		jmiSave.addActionListener(this);
		jmiAbout.addActionListener(this);
		jmiExit.addActionListener(this);
		setSize(600, 400);
		setLocationRelativeTo(null);
		setVisible(true);
		setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
	}

	public void actionPerformed(ActionEvent e) {
		if (e.getSource() == jmiOpen) {
			open();
		} else if (e.getSource() == jmiSave) {
			save();
		} else if (e.getSource() == jmiAbout) {
			JOptionPane.showMessageDialog(this, "用Java语言开发的记事本程序", "关于记事本", JOptionPane.INFORMATION_MESSAGE);
		} else if (e.getSource() == jmiExit)
			System.exit(0);
	}

	private void open() { // 打开文件方法
		if (chooser.showOpenDialog(this) == JFileChooser.APPROVE_OPTION) {
			File file = chooser.getSelectedFile();
			try {
				BufferedInputStream in = new BufferedInputStream(new FileInputStream(file));
				byte[] b = new byte[in.available()];
				in.read(b, 0, b.length);
				jta.append(new String(b, 0, b.length));
				in.close();
				jlbStatus.setText("打开文件：" + file.getName());
				this.setTitle(file.toString() + "-记事本");
			} catch (IOException ex) {
				jlbStatus.setText("打开文件错误：" + file.getName());
			}
		}
	}

	private void save() { // 保存文件方法
		if (chooser.showSaveDialog(this) == JFileChooser.APPROVE_OPTION) {
			File file = chooser.getSelectedFile();
			try {
				BufferedOutputStream out = new BufferedOutputStream(new FileOutputStream(file));
				byte[] b = (jta.getText()).getBytes();
				out.write(b, 0, b.length);
				out.close();
				jlbStatus.setText("文件已经保存：" + file.getName());
			} catch (IOException ex) {
				jlbStatus.setText("保存文件错误： " + file.getName());
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
				new NotePadDemo();
			}
		});
	}
}
