#include <cstdio>
#include <iostream>

int main() {
    double income;
    std::cin >> income;

    if (income <= 2000.00) {
        std::cout << "Isento";
    } else if (income <= 3000.00) {
        double tax = (income - 2000.00) * 0.08;
        printf("R$ %1.2f", tax);
    } else if (income <= 4500.00) {
        double tax = 1000.00 * 0.08 + (income - 3000.00) * 0.18;
        printf("R$ %1.2f", tax);
    } else {
        double tax = 1000.00 * 0.08 + 1500.00 * 0.18 + (income - 4500.00) * 0.28;
        printf("R$ %1.2f", tax);
    }

    return 0;
}