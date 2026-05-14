import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.BoxLayout;
import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JPasswordField;
import javax.swing.JTextField;
import javax.swing.SwingConstants;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.GridLayout;
import java.util.List;

public class WelcomeFrame extends JFrame {
    private static final Color WINDOW_BACKGROUND = new Color(241, 234, 223);
    private static final Color PANEL_BACKGROUND = new Color(252, 248, 242);
    private static final Color ACCENT_DARK = new Color(66, 82, 69);
    private static final Color ACCENT_MID = new Color(95, 117, 103);

    private final Auth auth;
    private final JComboBox<String> modeCombo = new JComboBox<>(new String[]{"双人对战", "人机对战"});
    private final PlayerAuthPanel blackPanel = new PlayerAuthPanel("黑棋玩家");
    private final PlayerAuthPanel whitePanel = new PlayerAuthPanel("白棋玩家");
    private final JLabel hintLabel = new JLabel("请先完成登录或注册", SwingConstants.CENTER);
    private final JLabel matchupLabel = new JLabel("历史数据：等待玩家登录", SwingConstants.CENTER);

    public WelcomeFrame(Auth auth) {
        this.auth = auth;

        setTitle("五子棋 - 欢迎");
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setContentPane(buildContent());
        setResizable(false);
        pack();
        setLocationRelativeTo(null);

        modeCombo.addActionListener(event -> onModeChanged());
        blackPanel.bindActions(() -> doLogin(blackPanel), () -> doRegister(blackPanel));
        whitePanel.bindActions(() -> doLogin(whitePanel), () -> doRegister(whitePanel));
        refreshRecentAccounts();
        onModeChanged();
    }

    private JPanel buildContent() {
        JPanel root = new JPanel(new BorderLayout(16, 16));
        root.setBackground(WINDOW_BACKGROUND);
        root.setBorder(BorderFactory.createEmptyBorder(18, 18, 18, 18));

        root.add(buildHeader(), BorderLayout.NORTH);
        root.add(buildCenter(), BorderLayout.CENTER);
        root.add(buildFooter(), BorderLayout.SOUTH);
        return root;
    }

    private JPanel buildHeader() {
        JPanel panel = createCard();
        panel.setLayout(new BorderLayout(8, 8));

        JLabel titleLabel = new JLabel("GOMOKU", SwingConstants.CENTER);
        titleLabel.setFont(new Font("Segoe UI", Font.BOLD, 30));
        titleLabel.setForeground(ACCENT_DARK);

        JLabel subtitleLabel = new JLabel("登录 / 注册 / 模式选择", SwingConstants.CENTER);
        subtitleLabel.setFont(new Font("Microsoft YaHei UI", Font.PLAIN, 14));
        subtitleLabel.setForeground(new Color(110, 90, 64));

        JPanel modePanel = new JPanel();
        modePanel.setOpaque(false);
        JLabel modeLabel = new JLabel("游戏模式：");
        modeCombo.setPreferredSize(new Dimension(160, 34));
        modePanel.add(modeLabel);
        modePanel.add(modeCombo);

        panel.add(titleLabel, BorderLayout.NORTH);
        panel.add(subtitleLabel, BorderLayout.CENTER);
        panel.add(modePanel, BorderLayout.SOUTH);
        return panel;
    }

    private JPanel buildCenter() {
        JPanel panel = new JPanel(new GridLayout(1, 2, 14, 0));
        panel.setOpaque(false);
        panel.add(blackPanel);
        panel.add(whitePanel);
        return panel;
    }

    private JPanel buildFooter() {
        JPanel panel = createCard();
        panel.setLayout(new BorderLayout(8, 8));

        hintLabel.setFont(new Font("Microsoft YaHei UI", Font.PLAIN, 14));
        hintLabel.setForeground(new Color(90, 80, 66));

        matchupLabel.setFont(new Font("Microsoft YaHei UI", Font.PLAIN, 13));
        matchupLabel.setForeground(new Color(105, 93, 79));

        JPanel infoPanel = new JPanel();
        infoPanel.setOpaque(false);
        infoPanel.setLayout(new BoxLayout(infoPanel, BoxLayout.Y_AXIS));
        infoPanel.add(hintLabel);
        infoPanel.add(Box.createVerticalStrut(4));
        infoPanel.add(matchupLabel);

        JButton startButton = createButton("开始对局", ACCENT_DARK);
        startButton.addActionListener(event -> startGame());

        panel.add(infoPanel, BorderLayout.CENTER);
        panel.add(startButton, BorderLayout.EAST);
        return panel;
    }

    private void onModeChanged() {
        boolean singlePlayer = modeCombo.getSelectedIndex() == 1;
        blackPanel.setRoleTitle(singlePlayer ? "玩家" : "黑棋玩家");
        whitePanel.setVisible(!singlePlayer);
        hintLabel.setText(singlePlayer ? "人机模式：仅需玩家账号" : "双人模式：请完成两位玩家账号操作");
        updateMatchupLabel();
        pack();
    }

    private void doLogin(PlayerAuthPanel panel) {
        AuthResult result = auth.login(panel.getAccount(), panel.getPassword());
        showResult(result, "登录");
        if (result.isSuccess()) {
            panel.setAuthenticatedPlayer(result.getPlayer());
            panel.setStatus("已登录：" + result.getPlayer().getName() + "（胜率 " + result.getPlayer().getWinRateText() + "）", new Color(60, 110, 74));
            refreshRecentAccounts();
            updateMatchupLabel();
        }
    }

    private void doRegister(PlayerAuthPanel panel) {
        AuthResult result = auth.register(panel.getNameInput(), panel.getAccount(), panel.getPassword());
        showResult(result, "注册");
        if (result.isSuccess()) {
            panel.setAuthenticatedPlayer(result.getPlayer());
            panel.setStatus("已注册并登录：" + result.getPlayer().getName() + "（新账号）", new Color(60, 110, 74));
            refreshRecentAccounts();
            updateMatchupLabel();
        }
    }

    private void refreshRecentAccounts() {
        List<String> accounts = auth.getRecentAccounts();
        blackPanel.setRecentAccounts(accounts);
        whitePanel.setRecentAccounts(accounts);
    }

    private void updateMatchupLabel() {
        boolean singlePlayer = modeCombo.getSelectedIndex() == 1;
        Player blackPlayer = blackPanel.getAuthenticatedPlayer();
        if (singlePlayer) {
            if (blackPlayer == null) {
                matchupLabel.setText("历史数据：请先登录玩家账号");
            } else {
                matchupLabel.setText("历史数据：" + blackPlayer.getName() + " 胜率 " + blackPlayer.getWinRateText()
                        + "，总场次 " + blackPlayer.getTotalGames());
            }
            return;
        }

        Player whitePlayer = whitePanel.getAuthenticatedPlayer();
        if (blackPlayer == null || whitePlayer == null) {
            matchupLabel.setText("历史数据：等待双方账号完成登录");
            return;
        }

        matchupLabel.setText("历史数据：" + blackPlayer.getName() + "(" + blackPlayer.getWinRateText() + ") vs "
                + whitePlayer.getName() + "(" + whitePlayer.getWinRateText() + ")");
    }

    private void startGame() {
        boolean singlePlayer = modeCombo.getSelectedIndex() == 1;
        Player blackPlayer = blackPanel.getAuthenticatedPlayer();

        if (blackPlayer == null) {
            JOptionPane.showMessageDialog(this, "请先完成玩家登录或注册。", "提示", JOptionPane.WARNING_MESSAGE);
            return;
        }

        GameSetup gameSetup;
        if (singlePlayer) {
            gameSetup = auth.createSinglePlayerSetup(blackPlayer);
        } else {
            Player whitePlayer = whitePanel.getAuthenticatedPlayer();
            SetupResult setupResult = auth.createDualPlayerSetup(blackPlayer, whitePlayer);
            if (!setupResult.isSuccess()) {
                JOptionPane.showMessageDialog(this, setupResult.getMessage(), "提示", JOptionPane.WARNING_MESSAGE);
                return;
            }
            gameSetup = setupResult.getGameSetup();
        }

        GomokuFrame gomokuFrame = new GomokuFrame(gameSetup);
        gomokuFrame.setVisible(true);
        dispose();
    }

    private void showResult(AuthResult result, String action) {
        if (result.isSuccess()) {
            JOptionPane.showMessageDialog(this, result.getMessage(), action + "成功", JOptionPane.INFORMATION_MESSAGE);
        } else {
            JOptionPane.showMessageDialog(this, result.getMessage(), action + "失败", JOptionPane.WARNING_MESSAGE);
        }
    }

    private JPanel createCard() {
        JPanel panel = new JPanel();
        panel.setBackground(PANEL_BACKGROUND);
        panel.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(new Color(223, 212, 199)),
                BorderFactory.createEmptyBorder(14, 16, 14, 16)
        ));
        return panel;
    }

    private JButton createButton(String text, Color background) {
        JButton button = new JButton(text);
        button.setFont(new Font("Microsoft YaHei UI", Font.BOLD, 14));
        button.setForeground(Color.WHITE);
        button.setBackground(background);
        button.setBorder(BorderFactory.createEmptyBorder(10, 20, 10, 20));
        button.setFocusPainted(false);
        button.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return button;
    }

    private class PlayerAuthPanel extends JPanel {
        private final JLabel roleTitle = new JLabel();
        private final JTextField nameField = new JTextField();
        private final JTextField accountField = new JTextField();
        private final JComboBox<String> recentAccountsCombo = new JComboBox<>();
        private final JPasswordField passwordField = new JPasswordField();
        private final JLabel statusLabel = new JLabel("未登录", SwingConstants.LEFT);

        private Player authenticatedPlayer;

        private PlayerAuthPanel(String title) {
            setLayout(new BoxLayout(this, BoxLayout.Y_AXIS));
            setBackground(PANEL_BACKGROUND);
            setBorder(BorderFactory.createCompoundBorder(
                    BorderFactory.createLineBorder(new Color(223, 212, 199)),
                    BorderFactory.createEmptyBorder(14, 14, 14, 14)
            ));

            roleTitle.setText(title);
            roleTitle.setFont(new Font("Microsoft YaHei UI", Font.BOLD, 17));
            roleTitle.setForeground(ACCENT_DARK);

            add(roleTitle);
            add(Box.createVerticalStrut(10));
            add(buildField("昵称（注册用）", nameField));
            add(Box.createVerticalStrut(8));
            add(buildRecentSelector());
            add(Box.createVerticalStrut(8));
            add(buildField("账号", accountField));
            add(Box.createVerticalStrut(8));
            add(buildField("密码", passwordField));
            add(Box.createVerticalStrut(12));

            JPanel actionPanel = new JPanel();
            actionPanel.setOpaque(false);
            JButton loginButton = createButton("登录", ACCENT_DARK);
            JButton registerButton = createButton("注册", ACCENT_MID);
            actionPanel.add(loginButton);
            actionPanel.add(registerButton);
            add(actionPanel);

            add(Box.createVerticalStrut(12));
            statusLabel.setFont(new Font("Microsoft YaHei UI", Font.PLAIN, 13));
            statusLabel.setForeground(new Color(108, 95, 80));
            add(statusLabel);

            putClientProperty("loginButton", loginButton);
            putClientProperty("registerButton", registerButton);

            recentAccountsCombo.addActionListener(event -> {
                if (recentAccountsCombo.getSelectedIndex() <= 0) {
                    return;
                }
                Object selected = recentAccountsCombo.getSelectedItem();
                if (selected != null) {
                    accountField.setText(selected.toString());
                }
            });
        }

        private JPanel buildRecentSelector() {
            JPanel container = new JPanel();
            container.setOpaque(false);
            container.setLayout(new BorderLayout(0, 4));

            JLabel titleLabel = new JLabel("最近账号");
            titleLabel.setFont(new Font("Microsoft YaHei UI", Font.PLAIN, 13));
            titleLabel.setForeground(new Color(116, 98, 77));

            recentAccountsCombo.setPreferredSize(new Dimension(220, 32));
            recentAccountsCombo.setMaximumRowCount(6);

            container.add(titleLabel, BorderLayout.NORTH);
            container.add(recentAccountsCombo, BorderLayout.CENTER);
            return container;
        }

        private JPanel buildField(String title, JTextField field) {
            JPanel container = new JPanel();
            container.setOpaque(false);
            container.setLayout(new BorderLayout(0, 4));

            JLabel titleLabel = new JLabel(title);
            titleLabel.setFont(new Font("Microsoft YaHei UI", Font.PLAIN, 13));
            titleLabel.setForeground(new Color(116, 98, 77));

            field.setPreferredSize(new Dimension(220, 32));
            container.add(titleLabel, BorderLayout.NORTH);
            container.add(field, BorderLayout.CENTER);
            return container;
        }

        private void bindActions(Runnable onLogin, Runnable onRegister) {
            JButton loginButton = (JButton) getClientProperty("loginButton");
            JButton registerButton = (JButton) getClientProperty("registerButton");
            loginButton.addActionListener(event -> onLogin.run());
            registerButton.addActionListener(event -> onRegister.run());
        }

        private void setRoleTitle(String title) {
            roleTitle.setText(title);
        }

        private void setRecentAccounts(List<String> accounts) {
            Object selectedBefore = recentAccountsCombo.getSelectedItem();
            recentAccountsCombo.removeAllItems();
            recentAccountsCombo.addItem("选择最近账号");
            for (String account : accounts) {
                recentAccountsCombo.addItem(account);
            }
            if (selectedBefore != null) {
                recentAccountsCombo.setSelectedItem(selectedBefore);
            }
        }

        private String getNameInput() {
            return nameField.getText();
        }

        private String getAccount() {
            return accountField.getText();
        }

        private char[] getPassword() {
            return passwordField.getPassword();
        }

        private void setAuthenticatedPlayer(Player player) {
            this.authenticatedPlayer = player;
        }

        private Player getAuthenticatedPlayer() {
            return authenticatedPlayer;
        }

        private void setStatus(String text, Color color) {
            statusLabel.setText(text);
            statusLabel.setForeground(color);
        }
    }
}
