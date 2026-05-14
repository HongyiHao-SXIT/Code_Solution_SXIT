import java.util.Arrays;
import java.util.List;

public class Auth {
    private final PlayerStore playerStore = new PlayerStore("data/players.db");

    public AuthResult login(String account, char[] password) {
        try {
            String normalizedAccount = normalize(account);
            if (normalizedAccount.isEmpty()) {
                return AuthResult.failure("账号不能为空。");
            }
            if (password == null || password.length == 0) {
                return AuthResult.failure("密码不能为空。");
            }

            Player player = playerStore.authenticate(normalizedAccount, password);
            if (player == null) {
                return AuthResult.failure("账号或密码错误。");
            }
            playerStore.rememberAccount(normalizedAccount);
            return AuthResult.success(player, "欢迎回来，" + player.getName() + "。当前积分：" + player.getScore());
        } finally {
            clearPassword(password);
        }
    }

    public AuthResult register(String name, String account, char[] password) {
        try {
            String normalizedName = normalize(name);
            String normalizedAccount = normalize(account);

            if (normalizedName.isEmpty()) {
                return AuthResult.failure("昵称不能为空。");
            }
            if (normalizedAccount.isEmpty()) {
                return AuthResult.failure("账号不能为空。");
            }
            if (password == null || password.length == 0) {
                return AuthResult.failure("密码不能为空。");
            }

            if (playerStore.findByAccount(normalizedAccount) != null) {
                return AuthResult.failure("账号已存在，请直接登录。");
            }

            Player player = new Player(normalizedName, normalizedAccount, playerStore.hashPassword(password), 0, 0, false);
            playerStore.save(player);
            playerStore.rememberAccount(normalizedAccount);
            return AuthResult.success(player, "注册成功，当前积分：0");
        } finally {
            clearPassword(password);
        }
    }

    public List<String> getRecentAccounts() {
        return playerStore.getRecentAccounts();
    }

    public GameSetup createSinglePlayerSetup(Player humanPlayer) {
        return new GameSetup(humanPlayer, Player.createComputer("电脑"), true, playerStore);
    }

    public SetupResult createDualPlayerSetup(Player blackPlayer, Player whitePlayer) {
        if (blackPlayer == null || whitePlayer == null) {
            return SetupResult.failure("请先完成两位玩家登录/注册。");
        }
        if (blackPlayer.getAccount().equals(whitePlayer.getAccount())) {
            return SetupResult.failure("双人模式下请使用两个不同账号。");
        }
        return SetupResult.success(new GameSetup(blackPlayer, whitePlayer, false, playerStore));
    }

    private String normalize(String text) {
        return text == null ? "" : text.trim();
    }

    private void clearPassword(char[] password) {
        if (password != null) {
            Arrays.fill(password, '\0');
        }
    }
}

class AuthResult {
    private final boolean success;
    private final Player player;
    private final String message;

    private AuthResult(boolean success, Player player, String message) {
        this.success = success;
        this.player = player;
        this.message = message;
    }

    public static AuthResult success(Player player, String message) {
        return new AuthResult(true, player, message);
    }

    public static AuthResult failure(String message) {
        return new AuthResult(false, null, message);
    }

    public boolean isSuccess() {
        return success;
    }

    public Player getPlayer() {
        return player;
    }

    public String getMessage() {
        return message;
    }
}

class SetupResult {
    private final boolean success;
    private final GameSetup gameSetup;
    private final String message;

    private SetupResult(boolean success, GameSetup gameSetup, String message) {
        this.success = success;
        this.gameSetup = gameSetup;
        this.message = message;
    }

    public static SetupResult success(GameSetup gameSetup) {
        return new SetupResult(true, gameSetup, "");
    }

    public static SetupResult failure(String message) {
        return new SetupResult(false, null, message);
    }

    public boolean isSuccess() {
        return success;
    }

    public GameSetup getGameSetup() {
        return gameSetup;
    }

    public String getMessage() {
        return message;
    }
}