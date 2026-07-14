#include <iostream>

int main() {
	constexpr double kPi = 3.14;
	int height = 0;
	int radius = 0;
	std::cin >> height >> radius;

	int bucketCount = static_cast<int>(20000 / (kPi * radius * radius * height)) + 1;
	std::cout << bucketCount;
	return 0;
}