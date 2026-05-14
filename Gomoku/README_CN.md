# 五子棋（Java Swing）

一个使用 Java Swing 开发的桌面五子棋游戏，支持双人对战、人机对战、悔棋、登录/注册和本地积分持久化。

---

## 功能特性

- 两种对局模式：双人对战、人机对战
- 支持悔棋（在人机模式中会回退到玩家回合）
- 支持登录与注册，账号信息本地存储
- 持久化统计：胜场与总场次
- 欢迎页支持“最近账号”记忆
- 对局窗口支持返回欢迎页

---

## 运行环境

- JDK 8 及以上（推荐 JDK 17）
- 已安装 Java 的 Windows/macOS/Linux 系统

---

## 项目结构

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

## 编译与运行

### 编译

```powershell
javac -d out\production\Gomoku src\*.java
```

### 运行

```powershell
java -cp out\production\Gomoku Main
```

---

## 数据持久化说明

- `data/players.db`：保存账号、密码哈希、昵称、胜场、总场次
- `data/recent_accounts.db`：保存最近登录账号，便于快捷选择

> 说明：
>
> 密码以哈希（SHA-256）形式存储，不保存明文。

---

## 对局说明

- 黑棋先手。
- 点击棋盘交叉点落子。
- 任意方向先连成五子获胜。
- 棋盘下满判平局。

---

## 常见问题

- 如果发现 `.class` 出现在 `src/` 下，请使用 `-d out\production\Gomoku` 重新编译。
- 如果无法启动，请先用 `java -version` 检查 Java 版本。

---

## 许可证

用于学习与演示。
