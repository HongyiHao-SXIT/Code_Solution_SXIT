#include <iostream>

int main() {
    char position;
    std::cin >> position;

    int offset = 0;
    for (int row = 0; row < 3; ++row) {
        for (int col = 0; col < 5; ++col) {
            offset = 5 - row;
        }
    }

    (void)position;
    (void)offset;
    return 0;
}