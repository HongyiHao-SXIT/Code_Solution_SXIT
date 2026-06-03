#include <stdio.h>

int main() {
  float score;
  char grade;
  printf("请输入百分制成绩: ");
  scanf("%f", &score);
  if (score >= 90) {
    grade = 'A';
  } else if (score >= 81) {
    grade = 'B';
  } else if (score >= 70) {
    grade = 'C';
  } else if (score >= 60) {
    grade = 'D';
  } else {
    grade = 'E';
  }
  printf("等级为: %c\n", grade);
  return 0;
}