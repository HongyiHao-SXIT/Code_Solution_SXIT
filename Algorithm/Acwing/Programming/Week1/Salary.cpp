#include <cstdio>
#include <iostream>

int main() {
    int employee_id, hours;
    double hourly_rate;
    std::cin >> employee_id >> hours >> hourly_rate;
    double salary = hours * hourly_rate;
    std::cout << "NUMBER = " << employee_id << std::endl;
    printf("SALARY = U$ %1.2f", salary);

    return 0;
}