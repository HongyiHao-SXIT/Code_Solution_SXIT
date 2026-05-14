public class FibonacciNumber {
    public static long fib(int n) {
        if (n == 1 || n == 2) {
            return 1;
        } else {
            return fib(n - 1) + fib(n - 2);
        }
    }

    public static void main(String[] args) {
        int n = 50;
        System.out.println("The " + n + "th Fibonacci number is: " + fib(n));
    }
}
