#include <iostream>
#include <string>

int main() {
    std::string inputNumber;
    std::cin >> inputNumber;

    for (int index = static_cast<int>(inputNumber.size()) - 1; index >= 0; --index) {
        std::cout << inputNumber[index];
    }
    return 0;
}