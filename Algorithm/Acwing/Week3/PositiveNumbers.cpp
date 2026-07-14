#include <iostream>

int main() {
    double value;
    int positiveCount = 0;

    for (int index = 0; index < 6; ++index) {
        std::cin >> value;
        if (value > 0) {
            ++positiveCount;
        }
    }

    std::cout << positiveCount << " positive numbers\n";

    return 0;
}