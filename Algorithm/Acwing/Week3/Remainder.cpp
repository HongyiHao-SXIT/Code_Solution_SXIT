#include <iostream>

int main() {
    int divisor;
    std::cin >> divisor;

    for (int number = 1; number < 10000; ++number) {
        if (number % divisor == 2) {
            std::cout << number << '\n';
        }
    }

    return 0;
}