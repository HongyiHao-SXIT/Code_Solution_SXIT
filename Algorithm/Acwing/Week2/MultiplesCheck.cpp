#include <iostream>

int main() {
    int firstValue, secondValue;
    std::cin >> firstValue >> secondValue;

    int larger = (firstValue > secondValue) ? firstValue : secondValue;
    int smaller = (firstValue > secondValue) ? secondValue : firstValue;

    if (larger % smaller == 0)
        std::cout << "Sao Multiplos";
    else
        std::cout << "Nao sao Multiplos";

    return 0;
}