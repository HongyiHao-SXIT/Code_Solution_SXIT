#include <iostream>

int main() {
    double sideA, sideB, sideC;
    std::cin >> sideA >> sideB >> sideC;

    // Sort sides in descending order
    if (sideB > sideA) {
        std::swap(sideA, sideB);
    }
    if (sideC > sideA) {
        std::swap(sideA, sideC);
    }
    if (sideC > sideB) {
        std::swap(sideB, sideC);
    }

    if (sideA >= sideB + sideC) {
        std::cout << "NAO FORMA TRIANGULO" << std::endl;
    } else {
        if (sideA * sideA == sideB * sideB + sideC * sideC)
            std::cout << "TRIANGULO RETANGULO" << std::endl;
        if (sideA * sideA > sideB * sideB + sideC * sideC)
            std::cout << "TRIANGULO OBTUSANGULO" << std::endl;
        if (sideA * sideA < sideB * sideB + sideC * sideC)
            std::cout << "TRIANGULO ACUTANGULO" << std::endl;
        if (sideA == sideB && sideB == sideC)
            std::cout << "TRIANGULO EQUILATERO" << std::endl;
        else if (sideA == sideB || sideB == sideC || sideC == sideA)
            std::cout << "TRIANGULO ISOSCELES" << std::endl;
    }

    return 0;
}