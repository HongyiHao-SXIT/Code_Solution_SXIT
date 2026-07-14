#include <iostream>

int main() {
    int size;
    std::cin >> size;

    int middle = size / 2;

    for (int row = 0; row <= middle; ++row) {
        for (int space = 0; space < middle - row; ++space) {
            std::cout << ' ';
        }
        for (int star = 0; star < 2 * row + 1; ++star) {
            std::cout << '*';
        }
        for (int space = 0; space < middle - row; ++space) {
            std::cout << ' ';
        }
        std::cout << '\n';
    }

    for (int row = middle - 1; row >= 0; --row) {
        for (int space = 0; space < middle - row; ++space) {
            std::cout << ' ';
        }
        for (int star = 0; star < 2 * row + 1; ++star) {
            std::cout << '*';
        }
        for (int space = 0; space < middle - row; ++space) {
            std::cout << ' ';
        }
        std::cout << '\n';
    }

    return 0;
}
