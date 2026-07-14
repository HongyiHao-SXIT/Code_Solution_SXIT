#include <cstdio>
#include <iostream>

int main() {
    int start_hour, end_hour;
    std::cin >> start_hour >> end_hour;

    int duration;
    if (end_hour > start_hour) {
        duration = end_hour - start_hour;
    } else {
        duration = end_hour + 24 - start_hour;
    }

    printf("O JOGO DUROU %d HORA(S)", duration);

    return 0;
}