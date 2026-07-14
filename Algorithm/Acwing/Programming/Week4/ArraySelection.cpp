#include <iomanip>
#include <iostream>

int main() {
    const int kSize = 100;
    double values[kSize];

    for (int i = 0; i < kSize; i++) {
        std::cin >> values[i];
    }

    for (int i = 0; i < kSize; i++) {
        if (values[i] <= 10) {
            std::cout << "A[" << i << "] = " << std::fixed << std::setprecision(1) << values[i]
                      << std::endl;
        }
    }

    return 0;
}