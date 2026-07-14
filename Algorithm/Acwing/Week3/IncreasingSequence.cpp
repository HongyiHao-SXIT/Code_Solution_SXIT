#include <iostream>

int main() {
    int limit;
    while (std::cin >> limit && limit != 0) {
        for (int currentNumber = 1; currentNumber <= limit; ++currentNumber) {
            if (currentNumber > 1) {
                std::cout << ' ';
            }
            std::cout << currentNumber;
        }
        std::cout << '\n';
    }

    return 0;
}
