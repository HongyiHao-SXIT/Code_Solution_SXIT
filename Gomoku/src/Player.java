public class Player {
    private String name;
    private String account;
    private String passwordHash;
    private int score;
    private int totalGames;
    private boolean computer;

    public Player() {
    }

    public Player(String name, String account, String passwordHash, int score, int totalGames, boolean computer) {
        this.name = name;
        this.account = account;
        this.passwordHash = passwordHash;
        this.score = score;
        this.totalGames = totalGames;
        this.computer = computer;
    }

    public static Player createComputer(String name) {
        return new Player(name, "AI", "", 0, 0, true);
    }

    public Player copy() {
        return new Player(name, account, passwordHash, score, totalGames, computer);
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public String getAccount() {
        return account;
    }

    public void setAccount(String account) {
        this.account = account;
    }

    public String getPasswordHash() {
        return passwordHash;
    }

    public void setPasswordHash(String passwordHash) {
        this.passwordHash = passwordHash;
    }

    public int getScore() {
        return score;
    }

    public void setScore(int score) {
        this.score = score;
    }

    public int getTotalGames() {
        return totalGames;
    }

    public void setTotalGames(int totalGames) {
        this.totalGames = totalGames;
    }

    public String getWinRateText() {
        if (totalGames <= 0) {
            return "--";
        }
        double winRate = (score * 100.0) / totalGames;
        return String.format("%.1f%%", winRate);
    }

    public boolean isComputer() {
        return computer;
    }

    public void setComputer(boolean computer) {
        this.computer = computer;
    }
}
