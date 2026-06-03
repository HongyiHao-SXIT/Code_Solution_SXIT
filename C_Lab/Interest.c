#include <math.h>
#include <stdio.h>

int main() {
  double p0 = 1000;
  double p1;
  double p2;
  double p3;
  double p4;
  double p5;
  double r1 = 0.015;
  double r2 = 0.021;
  double r3 = 0.0275;
  double r5 = 0.03;
  double r0 = 0.0035;

  p1 = p0 * (1 + 5 * r5);  // 一次存5年
  p2 = p0 * (1 + 2 * r2) * (1 + 3 * r3);  // 先存2年再存3年
  p3 = p0 * (1 + 3 * r3) * (1 + 2 * r2);  // 先存3年再存2年
  p4 = p0 * pow(1 + r1, 5);  // 连续存5次1年
  p5 = p0 * pow(1 + r0 / 4, 4 * 5);  // 活期存款

  printf("方案a: %.2f\n方案b: %.2f\n方案c: %.2f\n方案d: %.2f\n方案e: %.2f\n",
         p1, p2, p3, p4, p5);
  return 0;
}