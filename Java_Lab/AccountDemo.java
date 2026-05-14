public class AccountDemo {
    public static void main(String[] args) {
        Account myaccount;
        myaccount = new Account();
        myaccount.id = 1001;
        myaccount.name = "Lanyi_adict";
        myaccount.deposit(5000);
        myaccount.withdraw(3000);

        System.out.println("ID: " + myaccount.id);
        System.out.println("Name:" + myaccount.name);
        System.out.println("Balance:" + myaccount.balance);

    }
}
