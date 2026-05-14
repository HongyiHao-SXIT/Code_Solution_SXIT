import java.io.BufferedWriter;
import java.io.IOException;
import java.nio.ByteBuffer;
import java.nio.CharBuffer;
import java.nio.charset.StandardCharsets;
import java.nio.file.AtomicMoveNotSupportedException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.StandardCopyOption;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.ArrayList;
import java.util.Base64;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class PlayerStore {
    private final Path filePath;
    private final Path recentAccountsPath;
    private static final int MAX_RECENT_ACCOUNTS = 6;

    public PlayerStore(String fileName) {
        this.filePath = Paths.get(fileName);
        this.recentAccountsPath = filePath.resolveSibling("recent_accounts.db");
        initializeFile();
    }

    public Player findByAccount(String account) {
        Player player = loadPlayers().get(account);
        return player == null ? null : player.copy();
    }

    public Player authenticate(String account, char[] password) {
        Player player = loadPlayers().get(account);
        if (player == null) {
            return null;
        }

        if (matchesPassword(player, password)) {
            return player.copy();
        }
        return null;
    }

    public void save(Player player) {
        Map<String, Player> players = loadPlayers();
        players.put(player.getAccount(), player.copy());
        writePlayers(players);
    }

    public List<String> getRecentAccounts() {
        try {
            List<String> lines = Files.readAllLines(recentAccountsPath, StandardCharsets.UTF_8);
            List<String> accounts = new ArrayList<>();
            for (String line : lines) {
                String account = unescape(line.trim());
                if (!account.isEmpty() && !accounts.contains(account)) {
                    accounts.add(account);
                }
            }
            return accounts;
        } catch (IOException exception) {
            throw new IllegalStateException("无法读取最近账号文件: " + recentAccountsPath, exception);
        }
    }

    public void rememberAccount(String account) {
        String normalized = account == null ? "" : account.trim();
        if (normalized.isEmpty()) {
            return;
        }

        List<String> recent = getRecentAccounts();
        recent.remove(normalized);
        recent.add(0, normalized);
        while (recent.size() > MAX_RECENT_ACCOUNTS) {
            recent.remove(recent.size() - 1);
        }

        Path tempFile = recentAccountsPath.resolveSibling(recentAccountsPath.getFileName() + ".tmp");
        try (BufferedWriter writer = Files.newBufferedWriter(tempFile, StandardCharsets.UTF_8)) {
            for (String item : recent) {
                writer.write(encode(item));
                writer.newLine();
            }
            moveFile(tempFile, recentAccountsPath);
        } catch (IOException exception) {
            throw new IllegalStateException("无法写入最近账号文件: " + recentAccountsPath, exception);
        }
    }

    private Map<String, Player> loadPlayers() {
        Map<String, Player> players = new LinkedHashMap<>();
        try {
            List<String> lines = Files.readAllLines(filePath, StandardCharsets.UTF_8);
            for (String line : lines) {
                if (line.trim().isEmpty()) {
                    continue;
                }

                String[] parts = line.split("\\|", -1);
                if (parts.length < 4) {
                    continue;
                }

                Player player = new Player();
                player.setAccount(unescape(parts[0]));
                player.setPasswordHash(unescape(parts[1]));
                player.setName(unescape(parts[2]));
                player.setScore(parseScore(parts[3]));
                player.setTotalGames(parts.length >= 5 ? parseScore(parts[4]) : 0);
                players.put(player.getAccount(), player);
            }
        } catch (IOException exception) {
            throw new IllegalStateException("无法读取玩家数据文件: " + filePath, exception);
        }
        return players;
    }

    private void writePlayers(Map<String, Player> players) {
        Path tempFile = filePath.resolveSibling(filePath.getFileName() + ".tmp");
        try (BufferedWriter writer = Files.newBufferedWriter(tempFile, StandardCharsets.UTF_8)) {
            for (Player player : players.values()) {
                writer.write(encode(player.getAccount()) + "|"
                        + encode(player.getPasswordHash()) + "|"
                        + encode(player.getName()) + "|"
                        + player.getScore() + "|"
                        + player.getTotalGames());
                writer.newLine();
            }
            moveFile(tempFile, filePath);
        } catch (IOException exception) {
            throw new IllegalStateException("无法写入玩家数据文件: " + filePath, exception);
        }
    }

    private void initializeFile() {
        try {
            Path parent = filePath.getParent();
            if (parent != null) {
                Files.createDirectories(parent);
            }
            if (!Files.exists(filePath)) {
                Files.createFile(filePath);
            }
            if (!Files.exists(recentAccountsPath)) {
                Files.createFile(recentAccountsPath);
            }
        } catch (IOException exception) {
            throw new IllegalStateException("无法初始化玩家数据文件: " + filePath, exception);
        }
    }

    private void moveFile(Path tempFile, Path targetFile) throws IOException {
        try {
            Files.move(tempFile, targetFile, StandardCopyOption.REPLACE_EXISTING, StandardCopyOption.ATOMIC_MOVE);
        } catch (AtomicMoveNotSupportedException exception) {
            Files.move(tempFile, targetFile, StandardCopyOption.REPLACE_EXISTING);
        }
    }

    private int parseScore(String text) {
        try {
            return Integer.parseInt(text);
        } catch (NumberFormatException exception) {
            return 0;
        }
    }

    private String encode(String text) {
        return Base64.getEncoder().encodeToString(text.getBytes(StandardCharsets.UTF_8));
    }

    public String hashPassword(char[] password) {
        try {
            MessageDigest digest = MessageDigest.getInstance("SHA-256");
            ByteBuffer passwordBuffer = StandardCharsets.UTF_8.encode(CharBuffer.wrap(password));
            byte[] passwordBytes = new byte[passwordBuffer.remaining()];
            passwordBuffer.get(passwordBytes);
            byte[] hashBytes = digest.digest(passwordBytes);
            return Base64.getEncoder().encodeToString(hashBytes);
        } catch (NoSuchAlgorithmException exception) {
            throw new IllegalStateException("当前环境不支持 SHA-256", exception);
        }
    }

    private boolean matchesPassword(Player player, char[] password) {
        String hashedPassword = hashPassword(password);
        String storedPassword = player.getPasswordHash();

        if (hashedPassword.equals(storedPassword)) {
            return true;
        }

        if (String.valueOf(password).equals(storedPassword)) {
            player.setPasswordHash(hashedPassword);
            save(player);
            return true;
        }
        return false;
    }

    private String unescape(String text) {
        try {
            return new String(Base64.getDecoder().decode(text), StandardCharsets.UTF_8);
        } catch (IllegalArgumentException exception) {
            return text;
        }
    }
}