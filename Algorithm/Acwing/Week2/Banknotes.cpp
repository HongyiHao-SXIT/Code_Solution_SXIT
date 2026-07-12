#include <cstdio>
#include <iostream>

int main() {
    int amount;
    std::cin >> amount;
    std::cout << amount << std::endl;

    int count_100 = amount / 100;
    amount %= 100;
    int count_50 = amount / 50;
    amount %= 50;
    int count_20 = amount / 20;
    amount %= 20;
    int count_10 = amount / 10;
    amount %= 10;
    int count_5 = amount / 5;
    amount %= 5;
    int count_2 = amount / 2;
    amount %= 2;
    int count_1 = amount;

    printf("%d nota(s) de R$ 100,00\n", count_100);
    printf("%d nota(s) de R$ 50,00\n", count_50);
    printf("%d nota(s) de R$ 20,00\n", count_20);
    printf("%d nota(s) de R$ 10,00\n", count_10);
    printf("%d nota(s) de R$ 5,00\n", count_5);
    printf("%d nota(s) de R$ 2,00\n", count_2);
    printf("%d nota(s) de R$ 1,00\n", count_1);

    return 0;
}