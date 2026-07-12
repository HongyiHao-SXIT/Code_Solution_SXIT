#include <iostream>

int main() {
    int a, b, c, d;
    std::cin >> a >> b >> c >> d;
    int diff = a * b - c * d;
    std::cout << "DIFERENCA = " << diff << std::endl;

    return 0;
}