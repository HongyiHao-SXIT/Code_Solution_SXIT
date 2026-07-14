#include <iostream>

int main() {
    const int kSize = 20;
    int values[kSize];

    for (int i = 0; i < kSize; i++) {
        std::cin >> values[i];
    }

    for (int i = kSize - 1; i >= 0; i--) {
        std::cout << "N[" << kSize - 1 - i << "] = " << values[i] << std::endl;
    }

    return 0;
}