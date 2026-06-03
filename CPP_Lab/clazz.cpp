#include <iostream>
#include <string>
using namespace std;

class Date {
  int year;
  int month;
  int day;

 public:
  void setData() {
    cin >> year >> month >> day;
  }
  void show() {
    cout << year << "-" << month << "-" << day;
  }
};

class Employer {
  string name;
  string id;
  string dept;
  double salary;
  Date birth;

 public:
  void setData() {
    cout << "姓名 编号 部门 工资: ";
    cin >> name >> id >> dept >> salary;
    cout << "生日(年 月 日): ";
    birth.setData();
  }
  void show() {
    cout << "员工:" << name << " [" << id << "] 部门:" << dept
         << " 工资:" << salary << " 生日:";
    birth.show();
    cout << endl;
  }
};

int main() {
  Employer emp;
  emp.setData();
  emp.show();
  return 0;
}