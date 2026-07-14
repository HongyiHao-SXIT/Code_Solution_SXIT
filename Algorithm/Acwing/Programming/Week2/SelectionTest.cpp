#include <iostream>

int main() {
    int valueA, valueB, valueC, valueD;
    std::cin >> valueA >> valueB >> valueC >> valueD;

    if (valueB > valueC && valueD > valueA && valueC + valueD > valueA + valueB && valueC > 0 &&
        valueD > 0 && valueA % 2 == 0) {
        std::cout << "Valores aceitos";
    } else {
        std::cout << "Valores nao aceitos";
    }

    return 0;
}