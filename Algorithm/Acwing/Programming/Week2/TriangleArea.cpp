#include <cmath>
#include <cstdio>
#include <iostream>

int main() {
    double sideA, sideB, sideC;
    std::cin >> sideA >> sideB >> sideC;

    if (sideA + sideB > sideC && std::fabs(sideA - sideB) < sideC) {
        printf("Perimetro = %1.1f", sideA + sideB + sideC);
    } else {
        printf("Area = %1.1f", (sideA + sideB) * sideC / 2.0);
    }

    return 0;
}