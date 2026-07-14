#include <iomanip>
#include <iostream>

int main() {
    const int kSize = 12;
    char operation;
    double matrix[kSize][kSize];

    std::cin >> operation;

    for (int row = 0; row < kSize; row++) {
        for (int col = 0; col < kSize; col++) {
            std::cin >> matrix[row][col];
        }
    }

    double sum = 0.0;
    int count = 0;

    for (int row = 0; row < kSize; row++) {
        for (int col = 0; col < kSize; col++) {
            if (row > col && row + col > kSize - 1) {
                sum += matrix[row][col];
                count++;
            }
        }
    }

    if (operation == 'M') {
        sum /= count;
    }

    std::cout << std::fixed << std::setprecision(1) << sum << std::endl;

    return 0;
}
