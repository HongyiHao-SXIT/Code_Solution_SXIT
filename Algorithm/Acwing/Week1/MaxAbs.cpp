#include <cmath>
#include <iostream>

int main() {
    int firstValue, secondValue, thirdValue;
    std::cin >> firstValue >> secondValue >> thirdValue;

    int maxOfFirstTwo = (firstValue + secondValue + std::abs(firstValue - secondValue)) / 2;
    int largestValue = (maxOfFirstTwo + thirdValue + std::abs(maxOfFirstTwo - thirdValue)) / 2;
    std::cout << largestValue << " eh o maior" << std::endl;

    return 0;
}