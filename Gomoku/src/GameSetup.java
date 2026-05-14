public class GameSetup {
    private final Player blackPlayer;
    private final Player whitePlayer;
    private final boolean singlePlayer;
    private final PlayerStore playerStore;

    public GameSetup(Player blackPlayer, Player whitePlayer, boolean singlePlayer, PlayerStore playerStore) {
        this.blackPlayer = blackPlayer;
        this.whitePlayer = whitePlayer;
        this.singlePlayer = singlePlayer;
        this.playerStore = playerStore;
    }

    public Player getBlackPlayer() {
        return blackPlayer;
    }

    public Player getWhitePlayer() {
        return whitePlayer;
    }

    public boolean isSinglePlayer() {
        return singlePlayer;
    }

    public PlayerStore getPlayerStore() {
        return playerStore;
    }
}