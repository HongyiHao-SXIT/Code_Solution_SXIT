#include <iostream>
#include <string>
using namespace std;

class Vehicle {
 protected:
  string carName;
  string owner;
  string date;

 public:
  Vehicle(string cn, string o, string d) : carName(cn), owner(o), date(d) {}
  virtual void show() {
    cout << "车名: " << carName << " 车主: " << owner << " 购买日期: " << date;
  }
};

class Car : public Vehicle {
  int seats;

 public:
  Car(string cn, string o, string d, int s) : Vehicle(cn, o, d), seats(s) {}
  void show() {
    Vehicle::show();
    cout << " 座位数: " << seats << endl;
  }
};

class Truck : public Vehicle {
  double weight;

 public:
  Truck(string cn, string o, string d, double w) : Vehicle(cn, o, d), weight(w) {}
  void show() {
    Vehicle::show();
    cout << " 吨位: " << weight << endl;
  }
};

int main() {
  Vehicle *v1 = new Car("奥迪", "张三", "2023-01-01", 5);
  Vehicle *v2 = new Truck("东风", "李四", "2022-05-10", 15.5);
  v1->show();
  v2->show();
  delete v1;
  delete v2;
  return 0;
}