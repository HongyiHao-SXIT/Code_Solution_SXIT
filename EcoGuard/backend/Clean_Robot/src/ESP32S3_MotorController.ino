/*
 * ESP32S3 Motor Controller integrated with EcoGuard platform.
 *
 * Features:
 * 1) Auto register to /api/robot/register
 * 2) Heartbeat to /api/robot/heartbeat
 * 3) Receive platform commands and execute motor control
 * 4) Support NAVIGATE target feedback with simulated position movement
 */

#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <math.h>

// -------------------------
// Hardware config
// -------------------------
#define SERIAL_BAUD_RATE 115200

#define LEFT_MOTOR_IN1 2
#define LEFT_MOTOR_IN2 3
#define LEFT_MOTOR_EN 4
#define RIGHT_MOTOR_IN1 5
#define RIGHT_MOTOR_IN2 6
#define RIGHT_MOTOR_EN 7

#define OBSTACLE_FRONT_TRIG 8
#define OBSTACLE_FRONT_ECHO 9
#define OBSTACLE_REAR_TRIG 10
#define OBSTACLE_REAR_ECHO 11
#define OBSTACLE_RIGHT_TRIG 12
#define OBSTACLE_RIGHT_ECHO 13

#define EDGE_FRONT_TRIG 14
#define EDGE_FRONT_ECHO 15
#define EDGE_MIDDLE_TRIG 16
#define EDGE_MIDDLE_ECHO 17
#define EDGE_REAR_TRIG 18
#define EDGE_REAR_ECHO 19

#define BATTERY_PIN 20
#define MAX_DISTANCE 200
#define MOTOR_MAX_SPEED 255

// -------------------------
// Network config (edit here)
// -------------------------
const char* WIFI_SSID = "YOUR_SSID";
const char* WIFI_PASS = "YOUR_PASS";
const char* SERVER_BASE = "http://192.168.1.100:5000";
const char* DEVICE_ID = "robot-001";
const char* DEVICE_NAME = "ESP32S3-Robot";

const unsigned long SERVER_POLL_INTERVAL_MS = 1500;
const unsigned long WIFI_RETRY_INTERVAL_MS = 5000;

// -------------------------
// Control model
// -------------------------
enum ControlMode {
  MODE_MANUAL,
  MODE_EDGE_FOLLOW
};

struct ObstacleThresholds {
  int front = 30;
  int rear = 30;
  int right = 30;
} obstacleThresholds;

struct EdgeConfig {
  int targetDistance = 20;
  int threshold = 5;
} edgeConfig;

struct SensorData {
  int obstacleFront = 0;
  int obstacleRear = 0;
  int obstacleRight = 0;
  int edgeFront = 0;
  int edgeMiddle = 0;
  int edgeRear = 0;
  int battery = 0;
} sensorData;

ControlMode currentMode = MODE_MANUAL;

// Simulated map position fed back to platform
float simLat = 30.500000f;
float simLng = 114.300000f;
float targetLat = 0.0f;
float targetLng = 0.0f;
bool hasTarget = false;

String lastServerCommand = "";
unsigned long lastPollAt = 0;
unsigned long lastWiFiRetryAt = 0;
bool isRegistered = false;
bool isPaused = false;

int leftMotorSpeed = 0;
int rightMotorSpeed = 0;

// -------------------------
// Helpers
// -------------------------
void initUltrasonicSensor(int trigPin, int echoPin) {
  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);
  digitalWrite(trigPin, LOW);
}

int readUltrasonicDistance(int trigPin, int echoPin) {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);

  long duration = pulseIn(echoPin, HIGH, 25000);
  int distance = duration * 0.034 / 2;

  if (distance > MAX_DISTANCE) {
    distance = MAX_DISTANCE;
  }
  if (distance < 0) {
    distance = 0;
  }
  return distance;
}

void setMotorSpeed(int motor, int speed) {
  speed = constrain(speed, -MOTOR_MAX_SPEED, MOTOR_MAX_SPEED);

  if (motor == 0) {
    if (speed > 0) {
      digitalWrite(LEFT_MOTOR_IN1, HIGH);
      digitalWrite(LEFT_MOTOR_IN2, LOW);
      analogWrite(LEFT_MOTOR_EN, speed);
    } else if (speed < 0) {
      digitalWrite(LEFT_MOTOR_IN1, LOW);
      digitalWrite(LEFT_MOTOR_IN2, HIGH);
      analogWrite(LEFT_MOTOR_EN, -speed);
    } else {
      digitalWrite(LEFT_MOTOR_IN1, LOW);
      digitalWrite(LEFT_MOTOR_IN2, LOW);
      analogWrite(LEFT_MOTOR_EN, 0);
    }
  } else {
    if (speed > 0) {
      digitalWrite(RIGHT_MOTOR_IN1, HIGH);
      digitalWrite(RIGHT_MOTOR_IN2, LOW);
      analogWrite(RIGHT_MOTOR_EN, speed);
    } else if (speed < 0) {
      digitalWrite(RIGHT_MOTOR_IN1, LOW);
      digitalWrite(RIGHT_MOTOR_IN2, HIGH);
      analogWrite(RIGHT_MOTOR_EN, -speed);
    } else {
      digitalWrite(RIGHT_MOTOR_IN1, LOW);
      digitalWrite(RIGHT_MOTOR_IN2, LOW);
      analogWrite(RIGHT_MOTOR_EN, 0);
    }
  }
}

void applyDrive(int leftSpeed, int rightSpeed) {
  leftMotorSpeed = constrain(leftSpeed, -MOTOR_MAX_SPEED, MOTOR_MAX_SPEED);
  rightMotorSpeed = constrain(rightSpeed, -MOTOR_MAX_SPEED, MOTOR_MAX_SPEED);
  setMotorSpeed(0, leftMotorSpeed);
  setMotorSpeed(1, rightMotorSpeed);
}

void stopMotors() {
  applyDrive(0, 0);
}

int readBatteryLevel() {
  int raw = analogRead(BATTERY_PIN);
  int percent = map(raw, 0, 4095, 0, 100);
  return constrain(percent, 0, 100);
}

bool detectObstacles() {
  bool detected = false;
  if (sensorData.obstacleFront > 0 && sensorData.obstacleFront < obstacleThresholds.front) {
    detected = true;
  }
  if (sensorData.obstacleRear > 0 && sensorData.obstacleRear < obstacleThresholds.rear) {
    detected = true;
  }
  if (sensorData.obstacleRight > 0 && sensorData.obstacleRight < obstacleThresholds.right) {
    detected = true;
  }
  return detected;
}

void edgeFollowingControl() {
  int leftAverage = (sensorData.edgeFront + sensorData.edgeMiddle + sensorData.edgeRear) / 3;
  int error = leftAverage - edgeConfig.targetDistance;
  int correction = (int)(error * 5.0f);

  int baseSpeed = 150;
  int leftSpeed = constrain(baseSpeed - correction, 0, MOTOR_MAX_SPEED);
  int rightSpeed = constrain(baseSpeed + correction, 0, MOTOR_MAX_SPEED);

  applyDrive(leftSpeed, rightSpeed);
}

void sendSensorData() {
  StaticJsonDocument<512> doc;
  doc["type"] = "sensor";
  JsonObject data = doc.createNestedObject("data");

  JsonObject obstacles = data.createNestedObject("obstacles");
  obstacles["front"] = sensorData.obstacleFront;
  obstacles["rear"] = sensorData.obstacleRear;
  obstacles["right"] = sensorData.obstacleRight;

  JsonObject leftSide = data.createNestedObject("leftSide");
  leftSide["sensor1"] = sensorData.edgeFront;
  leftSide["sensor2"] = sensorData.edgeMiddle;
  leftSide["sensor3"] = sensorData.edgeRear;

  data["battery"] = sensorData.battery;

  serializeJson(doc, Serial);
  Serial.println();
}

void updateSimPositionByDrive() {
  // Dead-reckoning style feedback to platform.
  float avg = (leftMotorSpeed + rightMotorSpeed) * 0.5f;
  if (abs((int)avg) < 10) {
    return;
  }

  // Scale speed into tiny geo movement.
  float step = avg / 255.0f * 0.00002f;
  simLat += step;

  // Clamp valid geo range
  if (simLat > 90.0f) simLat = 90.0f;
  if (simLat < -90.0f) simLat = -90.0f;
  if (simLng > 180.0f) simLng = 180.0f;
  if (simLng < -180.0f) simLng = -180.0f;
}

void updateNavStep() {
  if (!hasTarget || isPaused) {
    return;
  }

  float dLat = targetLat - simLat;
  float dLng = targetLng - simLng;
  float dist = sqrtf(dLat * dLat + dLng * dLng);

  if (dist < 0.00003f) {
    hasTarget = false;
    stopMotors();
    Serial.println("[Nav] Target reached");
    return;
  }

  // Approach target smoothly
  float stepRatio = 0.12f;
  simLat += dLat * stepRatio;
  simLng += dLng * stepRatio;

  // Move forward while navigating
  applyDrive(140, 140);
}

void tryConnectWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    return;
  }
  if (millis() - lastWiFiRetryAt < WIFI_RETRY_INTERVAL_MS) {
    return;
  }
  lastWiFiRetryAt = millis();

  Serial.print("[WiFi] Connecting to ");
  Serial.println(WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
}

bool registerRobot() {
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }

  HTTPClient http;
  String url = String(SERVER_BASE) + "/api/robot/register";
  http.begin(url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<256> payload;
  payload["device_id"] = DEVICE_ID;
  payload["name"] = DEVICE_NAME;

  String out;
  serializeJson(payload, out);

  int code = http.POST(out);
  String body = http.getString();
  http.end();

  if (code == 200) {
    Serial.println("[Platform] Register success");
    isRegistered = true;
    return true;
  }

  // Device already exists should be treated as registered
  if (code == 409) {
    Serial.println("[Platform] Already registered");
    isRegistered = true;
    return true;
  }

  Serial.print("[Platform] Register failed code=");
  Serial.println(code);
  Serial.println(body);
  return false;
}

void executePlatformCommand(const String& command, JsonObject target) {
  if (command.length() == 0 || command == "IDLE") {
    return;
  }

  if (command != lastServerCommand) {
    Serial.print("[Platform] Command: ");
    Serial.println(command);
    lastServerCommand = command;
  }

  if (command == "FORWARD") {
    hasTarget = false;
    applyDrive(180, 180);
  } else if (command == "BACK") {
    hasTarget = false;
    applyDrive(-150, -150);
  } else if (command == "LEFT") {
    hasTarget = false;
    applyDrive(-120, 120);
  } else if (command == "RIGHT") {
    hasTarget = false;
    applyDrive(120, -120);
  } else if (command == "SLOW_FORWARD") {
    hasTarget = false;
    applyDrive(100, 100);
  } else if (command == "FAST_FORWARD") {
    hasTarget = false;
    applyDrive(230, 230);
  } else if (command == "SPIN_LEFT") {
    hasTarget = false;
    applyDrive(-180, 180);
  } else if (command == "SPIN_RIGHT") {
    hasTarget = false;
    applyDrive(180, -180);
  } else if (command == "STOP" || command == "HOLD_POSITION" || command == "CANCEL_NAVIGATION") {
    hasTarget = false;
    stopMotors();
  } else if (command == "PAUSE") {
    isPaused = true;
    stopMotors();
  } else if (command == "RESUME") {
    isPaused = false;
  } else if (command == "RETURN_HOME") {
    // Home behavior can be adjusted for your map.
    targetLat = 30.500000f;
    targetLng = 114.300000f;
    hasTarget = true;
  } else if (command == "DOCK") {
    stopMotors();
  } else if (command == "RESET") {
    hasTarget = false;
    isPaused = false;
    stopMotors();
  } else if (command == "PICK_TRASH") {
    stopMotors();
    Serial.println("[Actuator] Simulate grab action");
  } else if (command == "NAVIGATE") {
    if (target.containsKey("lat") && target.containsKey("lng")) {
      targetLat = target["lat"].as<float>();
      targetLng = target["lng"].as<float>();
      hasTarget = true;
      Serial.print("[Nav] New target: ");
      Serial.print(targetLat, 6);
      Serial.print(", ");
      Serial.println(targetLng, 6);
    }
  }
}

void pollPlatform() {
  if (WiFi.status() != WL_CONNECTED) {
    return;
  }
  if (!isRegistered) {
    registerRobot();
  }
  if (millis() - lastPollAt < SERVER_POLL_INTERVAL_MS) {
    return;
  }
  lastPollAt = millis();

  HTTPClient http;
  String url = String(SERVER_BASE) + "/api/robot/heartbeat";
  http.begin(url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<512> payload;
  payload["device_id"] = DEVICE_ID;
  payload["lat"] = simLat;
  payload["lng"] = simLng;
  payload["status"] = isPaused ? "PAUSED" : "ONLINE";
  payload["battery"] = sensorData.battery;
  JsonObject cfg = payload.createNestedObject("config");
  cfg["mode"] = (currentMode == MODE_EDGE_FOLLOW) ? "EDGE_FOLLOW" : "MANUAL";
  cfg["has_target"] = hasTarget;

  String out;
  serializeJson(payload, out);

  int code = http.POST(out);
  String resp = http.getString();
  http.end();

  if (code == 403) {
    isRegistered = false;
    registerRobot();
    return;
  }

  if (code != 200) {
    Serial.print("[Platform] Heartbeat failed code=");
    Serial.println(code);
    return;
  }

  StaticJsonDocument<768> doc;
  DeserializationError err = deserializeJson(doc, resp);
  if (err) {
    Serial.print("[Platform] JSON parse error: ");
    Serial.println(err.c_str());
    return;
  }

  String command = doc["command"] | "IDLE";
  JsonObject target = doc["target"].as<JsonObject>();
  executePlatformCommand(command, target);
}

void parseSerialCommand(const String& cmd) {
  String c = cmd;
  c.trim();
  if (c.length() == 0) {
    return;
  }

  if (c == "MODE_EDGE") {
    currentMode = MODE_EDGE_FOLLOW;
    Serial.println("[Local] mode=edge_follow");
  } else if (c == "MODE_MANUAL") {
    currentMode = MODE_MANUAL;
    Serial.println("[Local] mode=manual");
  } else if (c == "STOP") {
    stopMotors();
    hasTarget = false;
  }
}

void initMotors() {
  pinMode(LEFT_MOTOR_IN1, OUTPUT);
  pinMode(LEFT_MOTOR_IN2, OUTPUT);
  pinMode(LEFT_MOTOR_EN, OUTPUT);
  pinMode(RIGHT_MOTOR_IN1, OUTPUT);
  pinMode(RIGHT_MOTOR_IN2, OUTPUT);
  pinMode(RIGHT_MOTOR_EN, OUTPUT);
  stopMotors();
}

void setup() {
  Serial.begin(SERIAL_BAUD_RATE);
  Serial.println("[System] ESP32S3 Motor Controller booting...");

  initMotors();

  initUltrasonicSensor(OBSTACLE_FRONT_TRIG, OBSTACLE_FRONT_ECHO);
  initUltrasonicSensor(OBSTACLE_REAR_TRIG, OBSTACLE_REAR_ECHO);
  initUltrasonicSensor(OBSTACLE_RIGHT_TRIG, OBSTACLE_RIGHT_ECHO);
  initUltrasonicSensor(EDGE_FRONT_TRIG, EDGE_FRONT_ECHO);
  initUltrasonicSensor(EDGE_MIDDLE_TRIG, EDGE_MIDDLE_ECHO);
  initUltrasonicSensor(EDGE_REAR_TRIG, EDGE_REAR_ECHO);

  pinMode(BATTERY_PIN, INPUT);

  tryConnectWiFi();

  StaticJsonDocument<128> statusDoc;
  statusDoc["type"] = "status";
  JsonObject data = statusDoc.createNestedObject("data");
  data["system"] = "ready";
  data["version"] = "2.0.0";
  serializeJson(statusDoc, Serial);
  Serial.println();
}

void loop() {
  // Local serial control (optional)
  if (Serial.available() > 0) {
    String cmd = Serial.readStringUntil('\n');
    parseSerialCommand(cmd);
  }

  // Sensor refresh
  sensorData.obstacleFront = readUltrasonicDistance(OBSTACLE_FRONT_TRIG, OBSTACLE_FRONT_ECHO);
  sensorData.obstacleRear = readUltrasonicDistance(OBSTACLE_REAR_TRIG, OBSTACLE_REAR_ECHO);
  sensorData.obstacleRight = readUltrasonicDistance(OBSTACLE_RIGHT_TRIG, OBSTACLE_RIGHT_ECHO);

  sensorData.edgeFront = readUltrasonicDistance(EDGE_FRONT_TRIG, EDGE_FRONT_ECHO);
  sensorData.edgeMiddle = readUltrasonicDistance(EDGE_MIDDLE_TRIG, EDGE_MIDDLE_ECHO);
  sensorData.edgeRear = readUltrasonicDistance(EDGE_REAR_TRIG, EDGE_REAR_ECHO);

  sensorData.battery = readBatteryLevel();

  // Safety first
  if (detectObstacles()) {
    stopMotors();
    hasTarget = false;
    StaticJsonDocument<192> intr;
    intr["type"] = "interrupt";
    JsonObject d = intr.createNestedObject("data");
    d["type"] = "obstacle";
    d["message"] = "Obstacle detected";
    serializeJson(intr, Serial);
    Serial.println();
  } else {
    if (currentMode == MODE_EDGE_FOLLOW && !isPaused && !hasTarget) {
      edgeFollowingControl();
    }
  }

  // Platform sync and command execution
  tryConnectWiFi();
  pollPlatform();

  // Simulated movement feedback
  if (hasTarget) {
    updateNavStep();
  } else {
    updateSimPositionByDrive();
  }

  // Telemetry output every 500ms
  static unsigned long lastSend = 0;
  if (millis() - lastSend > 500) {
    sendSensorData();
    lastSend = millis();
  }

  delay(10);
}
