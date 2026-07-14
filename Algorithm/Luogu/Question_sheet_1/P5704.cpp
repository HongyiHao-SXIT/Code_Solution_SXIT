#include <iostream>

int main() {
    char lowerLetter;
    std::cin >> lowerLetter;

    std::cout << static_cast<char>(lowerLetter - 'a' + 'A') << '\n';
    return 0;
}