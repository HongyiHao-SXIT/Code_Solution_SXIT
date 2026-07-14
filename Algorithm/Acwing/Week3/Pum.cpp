#include <iostream>

int main() {
    int rowCount;
    int columnCount;
    std::cin >> rowCount >> columnCount;

    int currentNumber = 1;
    for (int rowIndex = 0; rowIndex < rowCount; ++rowIndex) {
        for (int columnIndex = 0; columnIndex < columnCount - 1; ++columnIndex) {
            std::cout << currentNumber << ' ';
            ++currentNumber;
        }

        std::cout << "PUM\n";
        ++currentNumber;
    }

    return 0;
}
