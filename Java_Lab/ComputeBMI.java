import java.util.Scanner;

public class ComputeBMI {
    public static void main(String[] args) {
        Scanner input = new Scanner(System.in);

        System.out.print("Enter weight in pounds: ");
        double weight = input.nextDouble();

        System.out.print("Enter height in inches: ");
        double height = input.nextDouble();

        double bmi = (weight * 703) / (height * height);

        System.out.printf("Your BMI is: %.2f\n", bmi);
    }
}