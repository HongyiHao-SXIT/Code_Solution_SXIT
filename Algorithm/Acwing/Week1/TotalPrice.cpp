#include <cstdio>
#include <iostream>

int main() {
    int code1, quantity1, code2, quantity2;
    double price1, price2;
    std::cin >> code1 >> quantity1 >> price1;
    std::cin >> code2 >> quantity2 >> price2;
    double total = quantity1 * price1 + quantity2 * price2;
    printf("VALOR A PAGAR: R$ %1.2f", total);

    return 0;
}