import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.BoxLayout;
import javax.swing.JButton;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.SwingConstants;
import javax.swing.Timer;
import java.awt.BasicStroke;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.GradientPaint;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.GridLayout;
import java.awt.Point;
import java.awt.RenderingHints;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.awt.geom.RoundRectangle2D;
import java.util.ArrayDeque;
import java.util.Deque;

public class GomokuFrame extends JFrame {
    private static final int BOARD_SIZE = 15;
    private static final int CELL_SIZE = 42;
    private static final int PADDING = 48;
    private static final int BLACK_PIECE = 1;
    private static final int WHITE_PIECE = 2;

    private static final Color WINDOW_BACKGROUND = new Color(241, 234, 223);
    private static final Color PANEL_BACKGROUND = new Color(252, 248, 242);
    private static final Color ACCENT_DARK = new Color(66, 82, 69);
    private static final Color ACCENT_LIGHT = new Color(192, 154, 97);
    private static final Color BOARD_LINE_COLOR = new Color(107, 74, 42);
    private static final Color LAST_MOVE_MARKER = new Color(214, 78, 56);

    private final Player blackPlayer;
    private final Player whitePlayer;
    private final boolean singlePlayer;
    private final PlayerStore playerStore;
    private final int[][] board = new int[BOARD_SIZE][BOARD_SIZE];
    private final Deque<Move> moveHistory = new ArrayDeque<>();

    private boolean blackTurn = true;
    private boolean gameOver = false;
    private boolean aiThinking = false;
    private boolean matchStatsApplied = false;
    private int blackScoreBefore;
    private int whiteScoreBefore;
    private int blackGamesBefore;
    private int whiteGamesBefore;

    private final JLabel subtitleLabel = new JLabel();
    private final JLabel statusLabel = new JLabel();
    private final JLabel scoreLabel = new JLabel();
    private final JLabel blackScoreInfoLabel = new JLabel();
    private final JLabel whiteScoreInfoLabel = new JLabel();
    private final BoardPanel boardPanel = new BoardPanel();
    private final JButton undoButton = createActionButton("悔棋", new Color(95, 117, 103));
    private final JButton restartButton = createActionButton("重新开始", ACCENT_DARK);
    private final JButton backButton = createActionButton("返回欢迎页", ACCENT_LIGHT);

    public GomokuFrame(GameSetup gameSetup) {
        this.blackPlayer = gameSetup.getBlackPlayer();
        this.whitePlayer = gameSetup.getWhitePlayer();
        this.singlePlayer = gameSetup.isSinglePlayer();
        this.playerStore = gameSetup.getPlayerStore();

        setTitle("五子棋");
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setContentPane(buildContentPanel());

        restartButton.addActionListener(event -> resetGame());
        undoButton.addActionListener(event -> undoMove());
        backButton.addActionListener(event -> backToWelcome());

        updateSubtitle();
        updateStatus();
        updateScoreLabel();
        updateUndoState();
        pack();
        setLocationRelativeTo(null);
        setResizable(false);
    }

    private JPanel buildContentPanel() {
        JPanel container = new JPanel(new BorderLayout(18, 18));
        container.setBackground(WINDOW_BACKGROUND);
        container.setBorder(BorderFactory.createEmptyBorder(18, 18, 18, 18));

        container.add(buildHeaderPanel(), BorderLayout.NORTH);
        container.add(buildCenterPanel(), BorderLayout.CENTER);
        container.add(buildFooterPanel(), BorderLayout.SOUTH);
        return container;
    }

    private JPanel buildHeaderPanel() {
        JPanel headerPanel = createCardPanel();
        headerPanel.setLayout(new BorderLayout(10, 8));

        JLabel titleLabel = new JLabel("GOMOKU", SwingConstants.CENTER);
        titleLabel.setFont(new Font("Segoe UI", Font.BOLD, 28));
        titleLabel.setForeground(ACCENT_DARK);

        subtitleLabel.setHorizontalAlignment(SwingConstants.CENTER);
        subtitleLabel.setFont(new Font("Microsoft YaHei UI", Font.PLAIN, 14));
        subtitleLabel.setForeground(new Color(117, 97, 69));

        statusLabel.setHorizontalAlignment(SwingConstants.CENTER);
        statusLabel.setFont(new Font("Microsoft YaHei UI", Font.BOLD, 16));
        statusLabel.setForeground(new Color(76, 66, 53));

        headerPanel.add(titleLabel, BorderLayout.NORTH);
        headerPanel.add(subtitleLabel, BorderLayout.CENTER);
        headerPanel.add(statusLabel, BorderLayout.SOUTH);
        return headerPanel;
    }

    private JPanel buildCenterPanel() {
        JPanel centerPanel = new JPanel(new BorderLayout(18, 0));
        centerPanel.setOpaque(false);

        centerPanel.add(boardPanel, BorderLayout.CENTER);
        centerPanel.add(buildInfoPanel(), BorderLayout.EAST);
        return centerPanel;
    }

    private JPanel buildInfoPanel() {
        JPanel infoPanel = createCardPanel();
        infoPanel.setLayout(new GridLayout(3, 1, 0, 12));
        infoPanel.setPreferredSize(new Dimension(220, 0));

        infoPanel.add(createInfoBlock("黑棋", blackPlayer.getName(), new Color(32, 32, 32), blackScoreInfoLabel));
        infoPanel.add(createInfoBlock("白棋", whitePlayer.getName(), new Color(245, 245, 245), whiteScoreInfoLabel));
        infoPanel.add(createTipsBlock());
        return infoPanel;
    }

    private JPanel createInfoBlock(String label, String name, Color pieceColor, JLabel scoreText) {
        JPanel panel = createCardPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));

        JLabel roleLabel = new JLabel(label);
        roleLabel.setFont(new Font("Microsoft YaHei UI", Font.BOLD, 14));
        roleLabel.setForeground(new Color(125, 104, 78));

        JLabel nameLabel = new JLabel(name);
        nameLabel.setFont(new Font("Microsoft YaHei UI", Font.BOLD, 18));
        nameLabel.setForeground(ACCENT_DARK);

        JPanel chip = new JPanel();
        chip.setOpaque(true);
        chip.setMaximumSize(new Dimension(110, 28));
        chip.setBackground(pieceColor);
        chip.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(new Color(182, 170, 156)),
                BorderFactory.createEmptyBorder(4, 10, 4, 10)
        ));

        JLabel chipLabel = new JLabel(label + "执子");
        chipLabel.setForeground(pieceColor.getRed() < 80 ? Color.WHITE : new Color(48, 48, 48));
        chip.add(chipLabel);

        scoreText.setFont(new Font("Microsoft YaHei UI", Font.PLAIN, 13));
        scoreText.setForeground(new Color(113, 101, 88));

        panel.add(roleLabel);
        panel.add(Box.createVerticalStrut(6));
        panel.add(nameLabel);
        panel.add(Box.createVerticalStrut(10));
        panel.add(chip);
        panel.add(Box.createVerticalStrut(10));
        panel.add(scoreText);
        return panel;
    }

    private JPanel createTipsBlock() {
        JPanel panel = createCardPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));

        JLabel titleLabel = new JLabel("对局提示");
        titleLabel.setFont(new Font("Microsoft YaHei UI", Font.BOLD, 14));
        titleLabel.setForeground(new Color(125, 104, 78));

        JLabel tipsLabel = new JLabel("<html>• 点击棋盘交叉点落子<br>• 悔棋可撤回最近一步<br>• 人机模式会回退到你的回合</html>");
        tipsLabel.setFont(new Font("Microsoft YaHei UI", Font.PLAIN, 13));
        tipsLabel.setForeground(new Color(101, 89, 76));

        panel.add(titleLabel);
        panel.add(Box.createVerticalStrut(10));
        panel.add(tipsLabel);
        return panel;
    }

    private JPanel buildFooterPanel() {
        JPanel footerPanel = createCardPanel();
        footerPanel.setLayout(new BorderLayout(12, 0));

        JPanel buttonPanel = new JPanel();
        buttonPanel.setOpaque(false);
        buttonPanel.add(restartButton);
        buttonPanel.add(undoButton);
        buttonPanel.add(backButton);

        scoreLabel.setFont(new Font("Microsoft YaHei UI", Font.BOLD, 15));
        scoreLabel.setForeground(new Color(82, 72, 60));

        footerPanel.add(buttonPanel, BorderLayout.WEST);
        footerPanel.add(scoreLabel, BorderLayout.CENTER);
        return footerPanel;
    }

    private JPanel createCardPanel() {
        JPanel panel = new JPanel();
        panel.setBackground(PANEL_BACKGROUND);
        panel.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(new Color(223, 212, 199)),
                BorderFactory.createEmptyBorder(14, 16, 14, 16)
        ));
        return panel;
    }

    private JButton createActionButton(String text, Color backgroundColor) {
        JButton button = new JButton(text);
        button.setFont(new Font("Microsoft YaHei UI", Font.BOLD, 14));
        button.setForeground(Color.WHITE);
        button.setBackground(backgroundColor);
        button.setBorder(BorderFactory.createEmptyBorder(10, 18, 10, 18));
        button.setFocusPainted(false);
        button.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return button;
    }

    private void resetGame() {
        for (int row = 0; row < BOARD_SIZE; row++) {
            for (int col = 0; col < BOARD_SIZE; col++) {
                board[row][col] = 0;
            }
        }
        moveHistory.clear();
        blackTurn = true;
        gameOver = false;
        aiThinking = false;
        matchStatsApplied = false;
        updateStatus();
        updateUndoState();
        boardPanel.repaint();
    }

    private void updateSubtitle() {
        String modeLabel = singlePlayer ? "人机博弈" : "双人对弈";
        subtitleLabel.setText(modeLabel + "  |  " + blackPlayer.getName() + " vs " + whitePlayer.getName());
    }

    private void updateStatus() {
        Player currentPlayer = blackTurn ? blackPlayer : whitePlayer;
        String pieceName = blackTurn ? "黑棋" : "白棋";
        if (gameOver) {
            statusLabel.setText("游戏结束，点击“重新开始”开始新对局");
        } else if (aiThinking) {
            statusLabel.setText("电脑正在思考...");
        } else {
            statusLabel.setText("当前回合：" + currentPlayer.getName() + "（" + pieceName + "）");
        }
    }

    private void updateScoreLabel() {
        String whiteText = whitePlayer.isComputer()
                ? whitePlayer.getName() + "：-"
                : whitePlayer.getName() + "：" + whitePlayer.getScore();
        scoreLabel.setText("积分  " + blackPlayer.getName() + "：" + blackPlayer.getScore() + "    " + whiteText);
        blackScoreInfoLabel.setText("胜场 " + blackPlayer.getScore() + " / 总场 " + blackPlayer.getTotalGames()
            + " / 胜率 " + blackPlayer.getWinRateText());
        whiteScoreInfoLabel.setText(whitePlayer.isComputer()
            ? "积分不计入排行"
            : "胜场 " + whitePlayer.getScore() + " / 总场 " + whitePlayer.getTotalGames()
            + " / 胜率 " + whitePlayer.getWinRateText());
    }

    private void updateUndoState() {
        undoButton.setEnabled(!moveHistory.isEmpty() && !aiThinking);
    }

    private boolean placePiece(int row, int col) {
        if (gameOver || aiThinking || board[row][col] != 0) {
            return false;
        }

        int currentPiece = blackTurn ? BLACK_PIECE : WHITE_PIECE;
        board[row][col] = currentPiece;
        moveHistory.push(new Move(row, col));
        updateUndoState();

        if (hasWinner(row, col, currentPiece)) {
            gameOver = true;
            Player winner = blackTurn ? blackPlayer : whitePlayer;
            applyMatchStats(winner);
            updateStatus();
            updateScoreLabel();
            boardPanel.repaint();
            JOptionPane.showMessageDialog(this,
                    winner.getName() + " 获胜！当前积分：" + winner.getScore(),
                    "对局结束",
                    JOptionPane.INFORMATION_MESSAGE);
            return true;
        }

        if (isBoardFull()) {
            gameOver = true;
            applyMatchStats(null);
            updateStatus();
            updateScoreLabel();
            boardPanel.repaint();
            JOptionPane.showMessageDialog(this, "棋盘已满，本局平局。", "对局结束", JOptionPane.INFORMATION_MESSAGE);
            return true;
        }

        blackTurn = !blackTurn;
        updateStatus();
        return true;
    }

    private void applyMatchStats(Player winner) {
        if (matchStatsApplied) {
            return;
        }

        blackScoreBefore = blackPlayer.getScore();
        whiteScoreBefore = whitePlayer.getScore();
        blackGamesBefore = blackPlayer.getTotalGames();
        whiteGamesBefore = whitePlayer.getTotalGames();

        if (!blackPlayer.isComputer()) {
            blackPlayer.setTotalGames(blackPlayer.getTotalGames() + 1);
            if (winner == blackPlayer) {
                blackPlayer.setScore(blackPlayer.getScore() + 1);
            }
            playerStore.save(blackPlayer);
        }

        if (!whitePlayer.isComputer()) {
            whitePlayer.setTotalGames(whitePlayer.getTotalGames() + 1);
            if (winner == whitePlayer) {
                whitePlayer.setScore(whitePlayer.getScore() + 1);
            }
            playerStore.save(whitePlayer);
        }

        matchStatsApplied = true;
    }

    private void rollbackMatchStats() {
        if (!matchStatsApplied) {
            return;
        }

        if (!blackPlayer.isComputer()) {
            blackPlayer.setScore(blackScoreBefore);
            blackPlayer.setTotalGames(blackGamesBefore);
            playerStore.save(blackPlayer);
        }
        if (!whitePlayer.isComputer()) {
            whitePlayer.setScore(whiteScoreBefore);
            whitePlayer.setTotalGames(whiteGamesBefore);
            playerStore.save(whitePlayer);
        }

        matchStatsApplied = false;
        updateScoreLabel();
    }

    private void undoMove() {
        if (moveHistory.isEmpty() || aiThinking) {
            return;
        }

        if (gameOver) {
            rollbackMatchStats();
        }

        removeLastMove();
        while (singlePlayer && !moveHistory.isEmpty() && moveHistory.size() % 2 == 1) {
            removeLastMove();
        }

        gameOver = false;
        blackTurn = moveHistory.size() % 2 == 0;
        updateStatus();
        updateUndoState();
        boardPanel.repaint();
    }

    private void backToWelcome() {
        int result = JOptionPane.showConfirmDialog(
                this,
                "返回欢迎页会结束当前对局，是否继续？",
                "返回确认",
                JOptionPane.OK_CANCEL_OPTION,
                JOptionPane.QUESTION_MESSAGE
        );
        if (result != JOptionPane.OK_OPTION) {
            return;
        }

        WelcomeFrame welcomeFrame = new WelcomeFrame(new Auth());
        welcomeFrame.setVisible(true);
        dispose();
    }

    private void removeLastMove() {
        Move lastMove = moveHistory.pop();
        board[lastMove.row][lastMove.col] = 0;
    }

    private void scheduleComputerMove() {
        aiThinking = true;
        updateStatus();
        updateUndoState();
        Timer timer = new Timer(280, event -> {
            aiThinking = false;
            Move bestMove = findBestMove();
            if (bestMove != null && placePiece(bestMove.row, bestMove.col)) {
                boardPanel.repaint();
            }
            updateStatus();
            updateUndoState();
        });
        timer.setRepeats(false);
        timer.start();
    }

    private Move findBestMove() {
        if (moveHistory.isEmpty()) {
            int center = BOARD_SIZE / 2;
            return new Move(center, center);
        }

        Move bestMove = null;
        int bestScore = Integer.MIN_VALUE;

        for (int row = 0; row < BOARD_SIZE; row++) {
            for (int col = 0; col < BOARD_SIZE; col++) {
                if (board[row][col] != 0 || !isCandidateCell(row, col)) {
                    continue;
                }

                int score = evaluateMove(row, col, WHITE_PIECE) + evaluateMove(row, col, BLACK_PIECE) * 2;
                score += 14 - (Math.abs(row - BOARD_SIZE / 2) + Math.abs(col - BOARD_SIZE / 2));

                if (bestMove == null || score > bestScore) {
                    bestScore = score;
                    bestMove = new Move(row, col);
                }
            }
        }
        return bestMove;
    }

    private boolean isCandidateCell(int row, int col) {
        for (int rowOffset = -2; rowOffset <= 2; rowOffset++) {
            for (int colOffset = -2; colOffset <= 2; colOffset++) {
                int targetRow = row + rowOffset;
                int targetCol = col + colOffset;
                if (!isWithinBoard(targetRow, targetCol)) {
                    continue;
                }
                if (board[targetRow][targetCol] != 0) {
                    return true;
                }
            }
        }
        return false;
    }

    private int evaluateMove(int row, int col, int piece) {
        if (createsWin(row, col, piece)) {
            return piece == WHITE_PIECE ? 100000 : 90000;
        }

        int score = 0;
        score += lineScore(row, col, piece, 1, 0);
        score += lineScore(row, col, piece, 0, 1);
        score += lineScore(row, col, piece, 1, 1);
        score += lineScore(row, col, piece, 1, -1);
        return score;
    }

    private boolean createsWin(int row, int col, int piece) {
        board[row][col] = piece;
        boolean winner = hasWinner(row, col, piece);
        board[row][col] = 0;
        return winner;
    }

    private int lineScore(int row, int col, int piece, int rowStep, int colStep) {
        int forward = countContinuous(row, col, rowStep, colStep, piece);
        int backward = countContinuous(row, col, -rowStep, -colStep, piece);
        int total = forward + backward;

        int score = 0;
        if (total >= 4) {
            score += 50000;
        } else if (total == 3) {
            score += 8000;
        } else if (total == 2) {
            score += 1200;
        } else if (total == 1) {
            score += 200;
        }

        if (isOpenEnd(row, col, piece, rowStep, colStep, forward)) {
            score += 180;
        }
        if (isOpenEnd(row, col, piece, -rowStep, -colStep, backward)) {
            score += 180;
        }
        return score;
    }

    private boolean isOpenEnd(int row, int col, int piece, int rowStep, int colStep, int distance) {
        int nextRow = row + rowStep * (distance + 1);
        int nextCol = col + colStep * (distance + 1);
        return isWithinBoard(nextRow, nextCol)
                && board[nextRow][nextCol] == 0;
    }

    private boolean isWithinBoard(int row, int col) {
        return row >= 0 && row < BOARD_SIZE && col >= 0 && col < BOARD_SIZE;
    }

    private boolean isBoardFull() {
        for (int row = 0; row < BOARD_SIZE; row++) {
            for (int col = 0; col < BOARD_SIZE; col++) {
                if (board[row][col] == 0) {
                    return false;
                }
            }
        }
        return true;
    }

    private boolean hasWinner(int row, int col, int piece) {
        return countContinuous(row, col, 1, 0, piece) + countContinuous(row, col, -1, 0, piece) >= 4
                || countContinuous(row, col, 0, 1, piece) + countContinuous(row, col, 0, -1, piece) >= 4
                || countContinuous(row, col, 1, 1, piece) + countContinuous(row, col, -1, -1, piece) >= 4
                || countContinuous(row, col, 1, -1, piece) + countContinuous(row, col, -1, 1, piece) >= 4;
    }

    private int countContinuous(int row, int col, int rowStep, int colStep, int piece) {
        int total = 0;
        int nextRow = row + rowStep;
        int nextCol = col + colStep;

        while (isWithinBoard(nextRow, nextCol)
                && board[nextRow][nextCol] == piece) {
            total++;
            nextRow += rowStep;
            nextCol += colStep;
        }
        return total;
    }

    private class BoardPanel extends JPanel {
        BoardPanel() {
            setPreferredSize(new Dimension(PADDING * 2 + CELL_SIZE * (BOARD_SIZE - 1),
                    PADDING * 2 + CELL_SIZE * (BOARD_SIZE - 1)));
            setOpaque(false);
            setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(new Color(226, 214, 200)),
                BorderFactory.createEmptyBorder(12, 12, 12, 12)
            ));
            addMouseListener(new MouseAdapter() {
                @Override
                public void mouseClicked(MouseEvent event) {
                    Point point = event.getPoint();
                    int col = Math.round((point.x - PADDING) / (float) CELL_SIZE);
                    int row = Math.round((point.y - PADDING) / (float) CELL_SIZE);

                    if (row < 0 || row >= BOARD_SIZE || col < 0 || col >= BOARD_SIZE) {
                        return;
                    }

                    int centerX = PADDING + col * CELL_SIZE;
                    int centerY = PADDING + row * CELL_SIZE;
                    if (Math.abs(point.x - centerX) > CELL_SIZE / 2 || Math.abs(point.y - centerY) > CELL_SIZE / 2) {
                        return;
                    }

                    if (placePiece(row, col)) {
                        repaint();
                        if (singlePlayer && !gameOver && !blackTurn) {
                            scheduleComputerMove();
                        }
                    }
                }
            });
        }

        @Override
        protected void paintComponent(Graphics graphics) {
            super.paintComponent(graphics);

            Graphics2D graphics2D = (Graphics2D) graphics.create();
            graphics2D.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
            graphics2D.setPaint(new GradientPaint(0, 0, new Color(239, 205, 145), getWidth(), getHeight(), new Color(208, 169, 108)));
            graphics2D.fill(new RoundRectangle2D.Double(0, 0, getWidth(), getHeight(), 24, 24));

            graphics2D.setColor(BOARD_LINE_COLOR);
            graphics2D.setStroke(new BasicStroke(1.2f));

            for (int index = 0; index < BOARD_SIZE; index++) {
                int offset = PADDING + index * CELL_SIZE;
                graphics2D.drawLine(PADDING, offset, PADDING + CELL_SIZE * (BOARD_SIZE - 1), offset);
                graphics2D.drawLine(offset, PADDING, offset, PADDING + CELL_SIZE * (BOARD_SIZE - 1));
            }

            drawStarPoint(graphics2D, 3, 3);
            drawStarPoint(graphics2D, 3, 11);
            drawStarPoint(graphics2D, 7, 7);
            drawStarPoint(graphics2D, 11, 3);
            drawStarPoint(graphics2D, 11, 11);

            for (int row = 0; row < BOARD_SIZE; row++) {
                for (int col = 0; col < BOARD_SIZE; col++) {
                    if (board[row][col] == 0) {
                        continue;
                    }
                    paintPiece(graphics2D, row, col, board[row][col] == BLACK_PIECE);
                }
            }

            paintLastMoveMarker(graphics2D);

            graphics2D.dispose();
        }

        private void drawStarPoint(Graphics2D graphics2D, int row, int col) {
            int centerX = PADDING + col * CELL_SIZE;
            int centerY = PADDING + row * CELL_SIZE;
            graphics2D.fillOval(centerX - 4, centerY - 4, 8, 8);
        }

        private void paintPiece(Graphics2D graphics2D, int row, int col, boolean blackPiece) {
            int centerX = PADDING + col * CELL_SIZE;
            int centerY = PADDING + row * CELL_SIZE;
            int pieceSize = 30;

            if (blackPiece) {
                graphics2D.setPaint(new GradientPaint(centerX - 10, centerY - 10, new Color(72, 72, 72), centerX + 10, centerY + 10, new Color(16, 16, 16)));
            } else {
                graphics2D.setPaint(new GradientPaint(centerX - 10, centerY - 10, new Color(255, 255, 255), centerX + 10, centerY + 10, new Color(224, 224, 224)));
            }
            graphics2D.fillOval(centerX - pieceSize / 2, centerY - pieceSize / 2, pieceSize, pieceSize);
            graphics2D.setColor(new Color(90, 90, 90));
            graphics2D.drawOval(centerX - pieceSize / 2, centerY - pieceSize / 2, pieceSize, pieceSize);
        }

        private void paintLastMoveMarker(Graphics2D graphics2D) {
            if (moveHistory.isEmpty()) {
                return;
            }

            Move lastMove = moveHistory.peek();
            int centerX = PADDING + lastMove.col * CELL_SIZE;
            int centerY = PADDING + lastMove.row * CELL_SIZE;
            graphics2D.setColor(LAST_MOVE_MARKER);
            graphics2D.fillOval(centerX - 4, centerY - 4, 8, 8);
        }
    }

    private static class Move {
        private final int row;
        private final int col;

        private Move(int row, int col) {
            this.row = row;
            this.col = col;
        }
    }
}