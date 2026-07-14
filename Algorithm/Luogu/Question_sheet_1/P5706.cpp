#include <iostream>
#include <iomanip>

int main() {
	double totalWeight = 0.0;
	int count = 0;
	std::cin >> totalWeight >> count;

	std::cout << std::fixed << std::setprecision(3) << totalWeight / count << '\n';
	std::cout << count * 2;
	return 0;
}