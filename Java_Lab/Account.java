public class Account {
    public int id;
    public String name;
    public double balance;

    public Account() {
    }

    public void deposit(double amount) {
        balance = balance + amount;
        System.out.println("Current deposit is : " + balance);
    }

    public void withdraw(double amount) {
        balance = balance - amount;
        System.out.println("Current withdraw is : " + balance);
    }
}
