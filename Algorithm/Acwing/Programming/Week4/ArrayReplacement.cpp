#include <iostream>

int main() {
    const int kSize = 10;
    int values[kSize];

    for (int i = 0; i < kSize; i++) {
        std::cin >> values[i];
    }

    for (int i = 0; i < kSize; i++) {
        if (values[i] <= 0) {
            values[i] = 1;
        }
        std::cout << "X[" << i << "] = " << values[i] << std::endl;
    }

    return 0;
}