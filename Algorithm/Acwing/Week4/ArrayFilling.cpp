#include <iostream>

int main() {
    const int kSize = 10;
    int values[kSize];
    int initialValue;

    std::cin >> initialValue;

    values[0] = initialValue;
    for (int i = 1; i < kSize; i++) {
        values[i] = values[i - 1] * 2;
    }

    for (int i = 0; i < kSize; i++) {
        std::cout << "N[" << i << "] = " << values[i] << std::endl;
    }

    return 0;
}