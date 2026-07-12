#include <cstdio>
#include <iostream>

int main() {
    const double pi = 3.14159;
    double a, b, c;
    std::cin >> a >> b >> c;

    printf("TRIANGULO: %1.3f\n", a * c * 0.5);
    printf("CIRCULO: %1.3f\n", pi * c * c);
    printf("TRAPEZIO: %1.3f\n", (a + b) * c / 2.0);
    printf("QUADRADO: %1.3f\n", b * b);
    printf("RETANGULO: %1.3f\n", a * b);

    return 0;
}