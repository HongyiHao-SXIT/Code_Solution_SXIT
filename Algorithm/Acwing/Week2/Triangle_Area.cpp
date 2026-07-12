#include <cmath>
#include <cstdio>
#include <iostream>

int main() {
    double a, b, c;
    std::cin >> a >> b >> c;

    if (a + b > c && std::fabs(a - b) < c) {
        printf("Perimetro = %1.1f", a + b + c);
    } else {
        printf("Area = %1.1f", (a + b) * c / 2.0);
    }

    return 0;
}