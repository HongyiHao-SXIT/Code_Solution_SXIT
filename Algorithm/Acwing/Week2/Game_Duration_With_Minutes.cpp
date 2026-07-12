#include <cstdio>
#include <iostream>

int main() {
    int start_hour, start_min, end_hour, end_min;
    std::cin >> start_hour >> start_min >> end_hour >> end_min;

    int total_start = start_hour * 60 + start_min;
    int total_end = end_hour * 60 + end_min;

    int duration;
    if (total_end > total_start) {
        duration = total_end - total_start;
    } else {
        duration = total_end + 24 * 60 - total_start;
    }

    int hours = duration / 60;
    int minutes = duration % 60;

    printf("O JOGO DUROU %d HORA(S) E %d MINUTO(S)", hours, minutes);

    return 0;
}