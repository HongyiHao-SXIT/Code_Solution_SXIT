#include <iostream>
#include <string>

int main () {
    std::string str;

    int num = 0;
    std::getline(std::cin, str);

    for (int i = 0; i < str.size(); i++) {
        if (str[i] >= '0' && str[i] <= '9') {
            num ++;
        }
    }
    std::cout << num << std::endl;
    return 0;

}