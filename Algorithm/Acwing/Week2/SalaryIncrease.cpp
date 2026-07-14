#include <cstdio>
#include <iostream>

int main() {
    double salary;
    std::cin >> salary;

    double new_salary, increase;
    int percentage;

    if (salary <= 400.00) {
        percentage = 15;
    } else if (salary <= 800.00) {
        percentage = 12;
    } else if (salary <= 1200.00) {
        percentage = 10;
    } else if (salary <= 2000.00) {
        percentage = 7;
    } else {
        percentage = 4;
    }

    new_salary = salary * (1.0 + percentage / 100.0);
    increase = salary * percentage / 100.0;

    printf("Novo salario: %1.2f\n", new_salary);
    printf("Reajuste ganho: %1.2f\n", increase);
    std::cout << "Em percentual: " << percentage << " %" << std::endl;

    return 0;
}