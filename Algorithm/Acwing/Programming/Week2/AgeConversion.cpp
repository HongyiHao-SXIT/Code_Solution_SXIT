#include <cstdio>
#include <iostream>

int main() {
    int total_days;
    std::cin >> total_days;

    int years = total_days / 365;
    total_days %= 365;
    int months = total_days / 30;
    int days = total_days % 30;

    printf("%d ano(s)\n", years);
    printf("%d mes(es)\n", months);
    printf("%d dia(s)\n", days);

    return 0;
}