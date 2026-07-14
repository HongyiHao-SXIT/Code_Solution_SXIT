#include <iostream>

int main() {
    double value;
    std::cin >> value;

    if (value < 0 || value > 100) {
        std::cout << "Fora de intervalo";
    } else if (value >= 0 && value <= 25) {
        std::cout << "Intervalo [0,25]";
    } else if (value > 25 && value <= 50) {
        std::cout << "Intervalo (25,50]";
    } else if (value > 50 && value <= 75) {
        std::cout << "Intervalo (50,75]";
    } else if (value > 75 && value <= 100) {
        std::cout << "Intervalo (75,100]";
    }

    return 0;
}