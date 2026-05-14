import javax.swing.BorderFactory;
import javax.swing.JOptionPane;
import javax.swing.SwingUtilities;
import javax.swing.UIManager;
import javax.swing.plaf.ColorUIResource;
import java.awt.Color;
import java.awt.Font;

public class Main {
    public static void main(String[] args) {
        SwingUtilities.invokeLater(() -> {
            try {
                UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
                applyUiDefaults();
            } catch (Exception exception) {
                JOptionPane.showMessageDialog(null,
                        "初始化界面失败：" + exception.getMessage(),
                        "启动失败",
                        JOptionPane.ERROR_MESSAGE);
                return;
            }

            Auth auth = new Auth();
            WelcomeFrame welcomeFrame = new WelcomeFrame(auth);
            welcomeFrame.setVisible(true);
        });
    }

    private static void applyUiDefaults() {
        Font primaryFont = new Font("Microsoft YaHei UI", Font.PLAIN, 14);
        Font titleFont = new Font("Microsoft YaHei UI", Font.BOLD, 14);

        UIManager.put("Label.font", primaryFont);
        UIManager.put("Button.font", titleFont);
        UIManager.put("OptionPane.messageFont", primaryFont);
        UIManager.put("OptionPane.buttonFont", titleFont);
        UIManager.put("TextField.font", primaryFont);
        UIManager.put("PasswordField.font", primaryFont);
        UIManager.put("Panel.background", new ColorUIResource(new Color(248, 243, 235)));
        UIManager.put("OptionPane.background", new ColorUIResource(new Color(248, 243, 235)));
        UIManager.put("Button.background", new ColorUIResource(new Color(56, 80, 68)));
        UIManager.put("Button.foreground", new ColorUIResource(Color.WHITE));
        UIManager.put("Button.border", BorderFactory.createEmptyBorder(8, 14, 8, 14));
    }
}