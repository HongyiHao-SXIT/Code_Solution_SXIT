#include <cmath>
#include <cstdio>
#include <iostream>

int main() {
    double coefficientA, coefficientB, coefficientC;
    std::cin >> coefficientA >> coefficientB >> coefficientC;

    if (coefficientA == 0 || coefficientB * coefficientB - 4 * coefficientA * coefficientC < 0) {
        std::cout << "Impossivel calcular";
    } else {
        double discriminant =
            std::sqrt(coefficientB * coefficientB - 4 * coefficientA * coefficientC);
        double root1 = (-coefficientB + discriminant) / (2 * coefficientA);
        double root2 = (-coefficientB - discriminant) / (2 * coefficientA);
        printf("R1 = %1.5f\n", root1);
        printf("R2 = %1.5f\n", root2);
    }

    return 0;
}