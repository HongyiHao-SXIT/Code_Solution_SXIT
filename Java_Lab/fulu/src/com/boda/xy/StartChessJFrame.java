package com.boda.xy;

import java.awt.event.*;
import java.awt.*;

import javax.swing.*;

public class StartChessJFrame extends JFrame {
	private ChessBoard chessBoard;
	private JPanel toolbar;
	private JButton startButton, backButton, exitButton;
	
	public StartChessJFrame() {
		setTitle("简单五子棋游戏");
		chessBoard = new ChessBoard();

		add(chessBoard);
		chessBoard.setOpaque(true);

		MyItemListener lis = new MyItemListener();
		toolbar = new JPanel();
		toolbar.setLayout(new FlowLayout(FlowLayout.CENTER));
		
		startButton = new JButton("重新开始");
		exitButton = new JButton("退出");
		backButton = new JButton("悔棋");
		
		toolbar.add(startButton);
		toolbar.add(exitButton);
		toolbar.add(backButton);

		startButton.addActionListener(lis);
		exitButton.addActionListener(lis);
		backButton.addActionListener(lis);

		add(toolbar, BorderLayout.SOUTH);
		add(chessBoard, BorderLayout.CENTER);
		
		setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
		pack();
	}

	private class MyItemListener implements ActionListener {
		public void actionPerformed(ActionEvent e) {
			Object obj = e.getSource();
			if (obj == startButton) {
				//System.out.println("重新开始");
				chessBoard.restartGame();
			} else if (obj == exitButton) {
				System.exit(0);
			} else if (obj == backButton) {
				//System.out.println("悔棋...");
				chessBoard.goback();
			}
		}
	}

	public static void main(String[] args) {		
		try {
			UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
		} catch (Exception e) {
		}
		StartChessJFrame f = new StartChessJFrame();
		f.setVisible(true);
	}
}
