#include <iostream>

int main() {
    int currentNumber;
    std::cin >> currentNumber;

    int printedCount = 0;
    while (printedCount < 6) {
        if (currentNumber % 2 != 0) {
            std::cout << currentNumber << '\n';
            ++printedCount;
        }
        ++currentNumber;
    }

    return 0;
}