# Gomoku (Java Swing)

A desktop Gomoku game built with Java Swing, supporting human vs human, human vs AI, undo, login/register, and local score persistence.

---

## Features

- Two game modes: Human vs Human, Human vs AI
- Undo support (in AI mode, undo restores to the player's turn)
- Login and registration with local account storage
- Persistent stats: wins and total games
- Recent account memory in welcome page
- Return to welcome page from game window

---

## Requirements

- JDK 8+ (recommended: JDK 17)
- Windows/macOS/Linux with Java installed

---

## Project Structure

```text
Gomoku/
  src/
    Main.java
    WelcomeFrame.java
    GomokuFrame.java
    Auth.java
    Player.java
    PlayerStore.java
    GameSetup.java
  data/
    players.db
    recent_accounts.db
  out/
    production/
      Gomoku/
```

---

## Build and Run

### Compile

```powershell
javac -d out\production\Gomoku src\*.java
```

### Run

```powershell
java -cp out\production\Gomoku Main
```

---

## Data Persistence

- `data/players.db`: account, password hash, nickname, wins, total games
- `data/recent_accounts.db`: recently used accounts for quick selection

> Note:
>
> Passwords are stored as hashes (SHA-256), not plain text.

---

## Gameplay Notes

- Black moves first.
- Click intersections to place pieces.
- Five in a row wins.
- Draw when board is full.

---

## Troubleshooting

- If classes are generated in `src/`, recompile with `-d out\production\Gomoku`.
- If the app cannot start, check your Java version with `java -version`.

---

## License

For learning/demo use.
