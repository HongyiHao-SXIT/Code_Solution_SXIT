#include <iostream>

int main() {
    int size;

    while (std::cin >> size && size != 0) {
        for (int row = 0; row < size; row++) {
            for (int col = 0; col < size; col++) {
                if (col != 0) {
                    std::cout << ' ';
                }
                std::cout << (1 << (row + col));
            }
            std::cout << std::endl;
        }

        std::cout << std::endl;
    }

    return 0;
}
