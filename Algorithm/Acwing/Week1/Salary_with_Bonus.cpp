#include <cstdio>
#include <iostream>
#include <string>

int main() {
    std::string name;
    double base_salary, sales;
    std::cin >> name;
    std::cin >> base_salary >> sales;
    double total = base_salary + sales * 0.15;
    printf("TOTAL = R$ %1.2f", total);

    return 0;
}