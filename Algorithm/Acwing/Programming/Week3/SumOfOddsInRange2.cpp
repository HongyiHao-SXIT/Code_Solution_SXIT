#include <algorithm>
#include <iostream>

int main() {
    int testCaseCount;
    std::cin >> testCaseCount;

    while (testCaseCount--) {
        int firstValue;
        int secondValue;
        std::cin >> firstValue >> secondValue;

        if (firstValue > secondValue) {
            std::swap(firstValue, secondValue);
        }

        int oddSum = 0;
        for (int currentValue = firstValue + 1; currentValue < secondValue; ++currentValue) {
            if (currentValue % 2 != 0) {
                oddSum += currentValue;
            }
        }

        std::cout << oddSum << '\n';
    }

    return 0;
}
