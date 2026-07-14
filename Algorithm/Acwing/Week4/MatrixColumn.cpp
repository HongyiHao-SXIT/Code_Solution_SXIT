#include <iomanip>
#include <iostream>

int main() {
    const int kSize = 12;
    int columnIndex;
    char operation;
    double matrix[kSize][kSize];

    std::cin >> columnIndex;
    std::cin >> operation;

    for (int row = 0; row < kSize; row++) {
        for (int col = 0; col < kSize; col++) {
            std::cin >> matrix[row][col];
        }
    }

    double sum = 0.0;
    for (int row = 0; row < kSize; row++) {
        sum += matrix[row][columnIndex];
    }

    if (operation == 'M') {
        sum /= kSize;
    }

    std::cout << std::fixed << std::setprecision(1) << sum << std::endl;

    return 0;
}
