#include <iostream>

int main() {
    int size;

    while (std::cin >> size && size != 0) {
        for (int row = 0; row < size; row++) {
            for (int col = 0; col < size; col++) {
                if (col != 0) {
                    std::cout << ' ';
                }
                std::cout << (row > col ? row - col + 1 : col - row + 1);
            }
            std::cout << std::endl;
        }

        std::cout << std::endl;
    }

    return 0;
}
