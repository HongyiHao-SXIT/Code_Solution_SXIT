#include <iostream>

bool isPerfectNumber(int number) {
    if (number == 1) {
        return false;
    }

    int divisorSum = 1;
    for (int divisor = 2; divisor * divisor <= number; ++divisor) {
        if (number % divisor == 0) {
            divisorSum += divisor;
            int pairedDivisor = number / divisor;
            if (pairedDivisor != divisor) {
                divisorSum += pairedDivisor;
            }
        }
    }

    return divisorSum == number;
}

int main() {
    int testCaseCount;
    std::cin >> testCaseCount;

    while (testCaseCount--) {
        int number;
        std::cin >> number;

        if (isPerfectNumber(number)) {
            std::cout << number << " is perfect" << std::endl;
        } else {
            std::cout << number << " is not perfect" << std::endl;
        }
    }

    return 0;
}
