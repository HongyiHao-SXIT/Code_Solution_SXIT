#include <iostream>
#include <cmath>

int main() {
    const int width = 5;
    const int centerRow = width / 2;

    for (int row = 0; row < width; ++row) {
        int distanceFromCenter = std::abs(row - centerRow);

        for (int col = 0; col < distanceFromCenter; ++col) {
            std::cout << " ";
        }
        for (int col = 0; col < (width - 2 * distanceFromCenter); ++col) {
            std::cout << "*";
        }
        std::cout << '\n';
    }

    return 0;
}