#include <iostream>

int main() {
    double coordinateX, coordinateY;
    std::cin >> coordinateX >> coordinateY;

    if (coordinateY == 0 && coordinateX != 0)
        std::cout << "Eixo X";
    else if (coordinateX == 0 && coordinateY != 0)
        std::cout << "Eixo Y";
    else if (coordinateX == 0 && coordinateY == 0)
        std::cout << "Origem";
    else if (coordinateX > 0 && coordinateY > 0)
        std::cout << "Q1";
    else if (coordinateX > 0 && coordinateY < 0)
        std::cout << "Q4";
    else if (coordinateX < 0 && coordinateY < 0)
        std::cout << "Q3";
    else if (coordinateX < 0 && coordinateY > 0)
        std::cout << "Q2";

    return 0;
}