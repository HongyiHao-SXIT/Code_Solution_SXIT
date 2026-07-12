#include <cstdio>
#include <iostream>

int main() {
    double value;
    std::cin >> value;
    int amount_cents = static_cast<int>(value * 100);

    int denominations[12] = {10000, 5000, 2000, 1000, 500, 200, 100, 50, 20, 10, 5, 1};
    int counts[12] = {};

    for (int i = 0; i < 12; i++) {
        counts[i] = amount_cents / denominations[i];
        amount_cents %= denominations[i];
    }

    printf("NOTAS:\n");
    printf("%d nota(s) de R$ 100.00\n", counts[0]);
    printf("%d nota(s) de R$ 50.00\n", counts[1]);
    printf("%d nota(s) de R$ 20.00\n", counts[2]);
    printf("%d nota(s) de R$ 10.00\n", counts[3]);
    printf("%d nota(s) de R$ 5.00\n", counts[4]);
    printf("%d nota(s) de R$ 2.00\n", counts[5]);
    printf("MOEDAS:\n");
    printf("%d moeda(s) de R$ 1.00\n", counts[6]);
    printf("%d moeda(s) de R$ 0.25\n", counts[7]);
    printf("%d moeda(s) de R$ 0.10\n", counts[9]);
    printf("%d moeda(s) de R$ 0.05\n", counts[10]);
    printf("%d moeda(s) de R$ 0.01\n", counts[11]);

    return 0;
}