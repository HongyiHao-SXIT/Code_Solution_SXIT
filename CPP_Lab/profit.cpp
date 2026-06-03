#include <iostream>
using namespace std;

// 1. 奖金计算函数
double calculateBonus(double profit) {
  double bonus = 0;
  if (profit <= 100000) {
    bonus = profit * 0.1;
  } else if (profit <= 200000) {
    bonus = 100000 * 0.1 + (profit - 100000) * 0.075;
  } else if (profit <= 400000) {
    bonus = 17500 + (profit - 200000) * 0.05;
  } else if (profit <= 600000) {
    bonus = 27500 + (profit - 400000) * 0.03;
  } else if (profit <= 1000000) {
    bonus = 33500 + (profit - 600000) * 0.015;
  } else {
    bonus = 39500 + (profit - 1000000) * 0.01;
  }
  return bonus;
}

// 2. 阶乘计算并输出
void printFactorials(int n) {
  long long fact = 1;
  for (int i = 0; i <= n; i++) {
    if (i == 0) {
      fact = 1;
    } else {
      fact *= i;
    }
    cout << i << "! = " << fact << endl;
  }
}

int main() {
  double profit;
  int num;
  cout << "请输入利润: ";
  cin >> profit;
  cout << "奖金提成为: " << calculateBonus(profit) << endl;
  cout << "请输入一个整数计算阶乘: ";
  cin >> num;
  printFactorials(num);
  return 0;
}