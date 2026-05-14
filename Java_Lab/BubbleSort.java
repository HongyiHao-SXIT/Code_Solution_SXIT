import java.util.Scanner;

public class BubbleSort {
    public static void main(String[] args) {
        double[] num = new double[5];

        Scanner input = new Scanner(System.in);
        System.out.println("Enter 5 numbers: ");

        for (int i = 0; i < num.length; i++) {
            num[i] = input.nextDouble();
        }

        for (int i = 0; i < num.length - 1; i++) {
            for (int j = 0; j < num.length - i - 1; j++) {
                if (num[j] > num[j + 1]) {
                    double temp = num[j];
                    num[j] = num[j + 1];
                    num[j + 1] = temp;
                }
            }
        }

        System.out.println("Sorted numbers:");
        for (double n : num) {
            System.out.print(n + " ");
        }
    }
}