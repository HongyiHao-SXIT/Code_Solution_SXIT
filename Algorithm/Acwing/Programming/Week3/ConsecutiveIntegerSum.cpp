#include <iostream>

int main() {
    int startValue;
    std::cin >> startValue;

    int termCount;
    std::cin >> termCount;
    while (termCount <= 0) {
        std::cin >> termCount;
    }

    int totalSum = 0;
    for (int currentValue = startValue; currentValue < startValue + termCount; ++currentValue) {
        totalSum += currentValue;
    }

    std::cout << totalSum << std::endl;

    return 0;
}