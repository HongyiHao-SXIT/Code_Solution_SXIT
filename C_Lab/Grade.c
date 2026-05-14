#include <stdio.h>

void avg_stu(float s[10][5]) {
    for (int i = 0; i < 10; i++) {
        float sum = 0;
        for (int j = 0; j < 5; j++) sum += s[i][j];
        printf("学生%d平均分: %.2f\n", i + 1, sum / 5);
    }
}

void find_max(float s[10][5]) {
    float max = s[0][0];
    int r = 0, c = 0;
    for (int i = 0; i < 10; i++)
        for (int j = 0; j < 5; j++)
            if (s[i][j] > max) { max = s[i][j]; r = i; c = j; }
    printf("最高分 %.2f 是学生%d的第%d门课\n", max, r + 1, c + 1);
}

int main() {
    float scores[10][5];
    printf("请输入10个学生5门课的成绩:\n");
    for (int i = 0; i < 10; i++)
        for (int j = 0; j < 5; j++) scanf("%f", &scores[i][j]);
    avg_stu(scores);
    find_max(scores);
    return 0;
}