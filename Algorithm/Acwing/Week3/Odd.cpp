#include <iostream>

int main() {
    int upperLimit;
    std::cin >> upperLimit;

    for (int number = 1; number <= upperLimit; ++number) {
        if (number % 2 != 0) {
            std::cout << number << '\n';
        }
    }

    return 0;
}