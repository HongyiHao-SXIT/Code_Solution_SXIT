#include <cmath>
#include <iomanip>
#include <iostream>

int main() {
	double sideA = 0.0;
	double sideB = 0.0;
	double sideC = 0.0;
	std::cin >> sideA >> sideB >> sideC;

	double semiPerimeter = (sideA + sideB + sideC) / 2.0;
	double area = std::sqrt(semiPerimeter * (semiPerimeter - sideA) *
							(semiPerimeter - sideB) * (semiPerimeter - sideC));

	std::cout << std::fixed << std::setprecision(1) << area;
	return 0;
}
