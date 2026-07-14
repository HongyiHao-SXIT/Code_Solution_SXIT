#include <iostream>
#include <string>

int main() {
    std::string text;
    std::getline(std::cin, text);

    int digitCount = 0;
    for (int index = 0; index < static_cast<int>(text.size()); index++) {
        if (text[index] >= '0' && text[index] <= '9') {
            digitCount++;
        }
    }

    std::cout << digitCount << std::endl;

    return 0;
}