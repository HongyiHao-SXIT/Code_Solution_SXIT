#include <iostream>
#include <string>

int main() {
    std::string input;
    getline(std::cin, input);

    std::cout << input.length() << std::endl;
    return 0;
}