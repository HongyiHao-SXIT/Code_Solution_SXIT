#include <iostream>

int main() {
    double a, b, c;
    std::cin >> a >> b >> c;

    // Sort sides in descending order
    if (b > a) std::swap(a, b);
    if (c > a) std::swap(a, c);
    if (c > b) std::swap(b, c);

    if (a >= b + c) {
        std::cout << "NAO FORMA TRIANGULO" << std::endl;
    } else {
        if (a * a == b * b + c * c)
            std::cout << "TRIANGULO RETANGULO" << std::endl;
        if (a * a > b * b + c * c)
            std::cout << "TRIANGULO OBTUSANGULO" << std::endl;
        if (a * a < b * b + c * c)
            std::cout << "TRIANGULO ACUTANGULO" << std::endl;
        if (a == b && b == c)
            std::cout << "TRIANGULO EQUILATERO" << std::endl;
        else if (a == b || b == c || c == a)
            std::cout << "TRIANGULO ISOSCELES" << std::endl;
    }

    return 0;
}