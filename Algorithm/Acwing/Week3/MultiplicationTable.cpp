#include <iostream>

int main() {
    int baseNumber;
    std::cin >> baseNumber;

    for (int multiplier = 1; multiplier <= 10; ++multiplier) {
        std::cout << multiplier << " x " << baseNumber << " = " << baseNumber * multiplier << '\n';
    }

    return 0;
}