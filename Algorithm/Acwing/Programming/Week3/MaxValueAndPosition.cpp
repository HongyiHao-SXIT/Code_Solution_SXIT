#include <iostream>

int main() {
    int maximumValue = 0;
    int maximumPosition = 0;

    for (int index = 1; index <= 100; ++index) {
        int currentValue;
        std::cin >> currentValue;
        if (currentValue > maximumValue) {
            maximumValue = currentValue;
            maximumPosition = index;
        }
    }

    std::cout << maximumValue << '\n' << maximumPosition << '\n';
    return 0;
}
