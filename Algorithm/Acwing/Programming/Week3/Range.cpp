#include <iostream>

int main() {
    int testCaseCount;
    int inRangeCount = 0;
    int outOfRangeCount = 0;

    std::cin >> testCaseCount;
    for (int index = 0; index < testCaseCount; ++index) {
        int number;
        std::cin >> number;
        if (number >= 10 && number <= 20) {
            ++inRangeCount;
        } else {
            ++outOfRangeCount;
        }
    }

    std::cout << inRangeCount << " in\n";
    std::cout << outOfRangeCount << " out\n";
    return 0;
}
