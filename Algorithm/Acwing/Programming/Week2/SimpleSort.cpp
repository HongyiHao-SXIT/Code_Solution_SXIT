#include <iostream>

int main() {
    int firstValue, secondValue, thirdValue;
    std::cin >> firstValue >> secondValue >> thirdValue;

    int originalFirst = firstValue;
    int originalSecond = secondValue;
    int originalThird = thirdValue;

    // Bubble sort the three values
    if (firstValue > secondValue) {
        std::swap(firstValue, secondValue);
    }
    if (secondValue > thirdValue) {
        std::swap(secondValue, thirdValue);
    }
    if (firstValue > secondValue) {
        std::swap(firstValue, secondValue);
    }

    std::cout << firstValue << std::endl
              << secondValue << std::endl
              << thirdValue << std::endl
              << std::endl
              << originalFirst << std::endl
              << originalSecond << std::endl
              << originalThird << std::endl;

    return 0;
}