#include <iostream>

int main() {
	int startHour = 0;
	int startMinute = 0;
	int endHour = 0;
	int endMinute = 0;
	std::cin >> startHour >> startMinute >> endHour >> endMinute;

	int startTotalMinutes = startHour * 60 + startMinute;
	int endTotalMinutes = endHour * 60 + endMinute;
	int durationMinutes = endTotalMinutes - startTotalMinutes;

	std::cout << durationMinutes / 60 << " " << durationMinutes % 60;
	return 0;
}
