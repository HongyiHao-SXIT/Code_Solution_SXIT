#include <iostream>

int main() {
    int valueA, valueB, valueC, valueD;
    std::cin >> valueA >> valueB >> valueC >> valueD;

    int difference = valueA * valueB - valueC * valueD;
    std::cout << "DIFERENCA = " << difference << std::endl;

    return 0;
}