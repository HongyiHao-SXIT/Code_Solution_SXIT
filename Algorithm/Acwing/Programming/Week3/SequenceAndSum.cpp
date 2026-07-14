#include <iostream>

int main() {
    int firstValue, secondValue;

    while (std::cin >> firstValue >> secondValue) {
        if (firstValue <= 0 || secondValue <= 0) {
            break;
        }

        int lowerValue = firstValue < secondValue ? firstValue : secondValue;
        int upperValue = firstValue > secondValue ? firstValue : secondValue;

        int totalSum = 0;
        for (int currentValue = lowerValue; currentValue <= upperValue; ++currentValue) {
            if (currentValue > lowerValue) {
                std::cout << ' ';
            }
            std::cout << currentValue;
            totalSum += currentValue;
        }

        std::cout << " Sum=" << totalSum << '\n';
    }

    return 0;
}
