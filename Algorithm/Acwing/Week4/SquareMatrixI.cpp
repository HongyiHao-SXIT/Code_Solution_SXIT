#include <algorithm>
#include <iomanip>
#include <iostream>

int main() {
    int size;

    while (std::cin >> size && size != 0) {
        for (int row = 0; row < size; row++) {
            for (int col = 0; col < size; col++) {
                int layer =
                    std::min(std::min(row, col), std::min(size - 1 - row, size - 1 - col)) + 1;
                if (col != 0) {
                    std::cout << ' ';
                }
                std::cout << std::setw(3) << layer;
            }
            std::cout << std::endl;
        }

        std::cout << std::endl;
    }

    return 0;
}
