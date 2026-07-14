#include <iostream>

bool isPrime(int number) {
    if (number < 2) {
        return false;
    }

    for (int divisor = 2; divisor * divisor <= number; ++divisor) {
        if (number % divisor == 0) {
            return false;
        }
    }

    return true;
}

int main() {
    int testCaseCount;
    std::cin >> testCaseCount;

    while (testCaseCount--) {
        int number;
        std::cin >> number;

        if (isPrime(number)) {
            std::cout << number << " is prime" << std::endl;
        } else {
            std::cout << number << " is not prime" << std::endl;
        }
    }

    return 0;
}
