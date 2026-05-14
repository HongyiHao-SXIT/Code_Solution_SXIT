package com.boda.xy;
import java.awt.GridLayout;
import javax.swing.ImageIcon;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.SwingConstants;
import javax.swing.SwingUtilities;
import javax.swing.UIManager;
public class Exercise01 extends JFrame {
	JLabel[] label = { new JLabel("", SwingConstants.CENTER), 
new JLabel("", SwingConstants.CENTER),
			            new JLabel("", SwingConstants.CENTER), 
new JLabel("", SwingConstants.CENTER) };
	public Exercise01() {
		setLayout(new GridLayout(1, 4)); // 设置布局管理器
		int[] deck = new int[52];
		// 初始化每一张牌
		for (int i = 0; i < deck.length; i++)
			deck[i] = i;
		// 打乱牌的次序
		for (int i = 0; i < deck.length; i++) {
			// 随机产生一个元素下标0~51
			int index = (int) (Math.random() * deck.length);
			int temp = deck[i]; // 将当前元素与产生的元素交换
			deck[i] = deck[index];
			deck[index] = temp;
		}
		// 显示输出前4张牌
		ImageIcon[] cards = new ImageIcon[4];
		for (int i = 0; i < 4; i++) {
			cards[i] = new ImageIcon(
"image\\card\\" + (deck[i] + 1) + ".png");
			label[i].setIcon(cards[i]);
			add(label[i]);
		}
		setTitle("任意抽取4张牌");
		setSize(350, 150);
		setLocationRelativeTo(null);
		setVisible(true);
		setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
	}
	public static void main(String[] args) {
		try {
			UIManager.setLookAndFeel(
UIManager.getSystemLookAndFeelClassName());
		} catch (Exception e) {
		}
		SwingUtilities.invokeLater(new Runnable() {
			public void run() {
				new Exercise01();
			}
		});
	}
}



