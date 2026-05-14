#include <stdio.h>
#define PI 3.1415926

int main() {
    float r, h, l, s, sq, vq, vz;
    printf("请输入圆半径r和圆柱高h: ");
    scanf("%f %f", &r, &h);
    l = 2 * PI * r;
    s = PI * r * r;
    sq = 4 * PI * r * r;
    vq = (4.0 / 3.0) * PI * r * r * r;
    vz = s * h;
    printf("圆周长: l=%.2f\n圆面积: s=%.2f\n圆球表面积: sq=%.2f\n圆球体积: vq=%.2f\n圆柱体积: vz=%.2f\n", l, s, sq, vq, vz);
    return 0;
}