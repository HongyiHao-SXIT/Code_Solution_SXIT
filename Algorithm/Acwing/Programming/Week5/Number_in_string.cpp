#include <iostream>
#include <string>

int main() {
    std::string input;
    int count = 0;
    getline(std::cin, input);

    for (int i = 0; i < input.length(); i ++ ) {
        if ( input[i] >= '0' && input[i] <= '9') {
            count++;
        }
    }
    std::cout << count << std::endl;
    return 0;
    
}