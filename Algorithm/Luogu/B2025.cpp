#include <iostream>
#include <cmath>

int main() {
    int n = 5;
    int center = n / 2;

    for (int i = 0; i < n; i++) {
        int distance = std::abs(i - center);

        for (int j = 0; j < distance; j++) {
            std::cout << " ";
        }
        for (int j = 0; j < (n - 2 * distance); j++) {
            std::cout << "*";
        }
        std::cout << std::endl;
    }

    return 0;
}