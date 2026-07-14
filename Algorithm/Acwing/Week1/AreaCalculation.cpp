#include <cstdio>
#include <iostream>

int main() {
    const double kPi = 3.14159;
    double valueA, valueB, valueC;
    std::cin >> valueA >> valueB >> valueC;

    printf("TRIANGULO: %1.3f\n", valueA * valueC * 0.5);
    printf("CIRCULO: %1.3f\n", kPi * valueC * valueC);
    printf("TRAPEZIO: %1.3f\n", (valueA + valueB) * valueC / 2.0);
    printf("QUADRADO: %1.3f\n", valueB * valueB);
    printf("RETANGULO: %1.3f\n", valueA * valueB);

    return 0;
}