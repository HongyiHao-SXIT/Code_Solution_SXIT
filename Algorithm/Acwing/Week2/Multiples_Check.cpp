#include <iostream>

int main() {
    int a, b;
    std::cin >> a >> b;

    int larger = (a > b) ? a : b;
    int smaller = (a > b) ? b : a;

    if (larger % smaller == 0)
        std::cout << "Sao Multiplos";
    else
        std::cout << "Nao sao Multiplos";

    return 0;
}