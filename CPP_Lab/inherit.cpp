#include <iostream>
#include <string>
using namespace std;

// 复用上个实验的Employer类作为基类
class Employer {
protected:
    string name;
public:
    virtual void getData() { cout << "输入姓名: "; cin >> name; }
    virtual void show() { cout << "姓名: " << name; }
};

class HourWorker : public Employer {
    double hour, wage;
public:
    void getData() {
        Employer::getData();
        cout << "工作时长 时薪: "; cin >> hour >> wage;
    }
    void show() {
        Employer::show();
        cout << " 工资: " << hour * wage << endl;
    }
};

class Admin : public Employer {
    double bonus;
public:
    void getData() {
        Employer::getData();
        cout << "行政补贴: "; cin >> bonus;
    }
    void show() {
        Employer::show();
        cout << " 补贴: " << bonus << endl;
    }
};

int main() {
    HourWorker hw; hw.getData(); hw.show();
    Admin ad; ad.getData(); ad.show();
    return 0;
}