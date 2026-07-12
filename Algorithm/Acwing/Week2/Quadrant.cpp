#include <iostream>

int main() {
    double x, y;
    std::cin >> x >> y;

    if (y == 0 && x != 0)
        std::cout << "Eixo X";
    else if (x == 0 && y != 0)
        std::cout << "Eixo Y";
    else if (x == 0 && y == 0)
        std::cout << "Origem";
    else if (x > 0 && y > 0)
        std::cout << "Q1";
    else if (x > 0 && y < 0)
        std::cout << "Q4";
    else if (x < 0 && y < 0)
        std::cout << "Q3";
    else if (x < 0 && y > 0)
        std::cout << "Q2";

    return 0;
}