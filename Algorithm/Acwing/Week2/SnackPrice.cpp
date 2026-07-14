#include <cstdio>
#include <iostream>

int main() {
    int item_code, quantity;
    std::cin >> item_code >> quantity;

    double prices[] = {0.0, 4.00, 4.50, 5.00, 2.00, 1.50};
    double total = prices[item_code] * quantity;

    printf("Total: R$ %1.2f\n", total);

    return 0;
}