#include <Arduino.h>
#include <WiFi.h>
#include <WebServer.h>
#include <ESPmDNS.h>
#include <WiFiUdp.h>
#include <ArduinoOTA.h>
#include <ESPAsyncWebServer.h>
#include <AsyncWebSocket.h>
#include <ArduinoJson.h>
#include "esp_camera.h"
#include <FS.h>
#include <SPIFFS.h>
#include <HTTPClient.h>

const char* WIFI_SSID = "YourWiFiSSID";
const char* WIFI_PASS = "YourWiFiPassword";
const char* SERVER_BASE = "http://192.168.1.100:5000";
const char* ROBOT_DEVICE_ID = "ESP32CAM-001";
const char* ROBOT_NAME = "ESP32-CAM";

#define HTTP_SERVER_PORT 8080
#define WEBSOCKET_PORT 81

#define PWDN_GPIO_NUM     32
#define RESET_GPIO_NUM    -1
#define XCLK_GPIO_NUM      0
#define SIOD_GPIO_NUM     26
#define SIOC_GPIO_NUM     27
#define Y9_GPIO_NUM       35
#define Y8_GPIO_NUM       34
#define Y7_GPIO_NUM       39
#define Y6_GPIO_NUM       36
#define Y5_GPIO_NUM       21
#define Y4_GPIO_NUM       19
#define Y3_GPIO_NUM       18
#define Y2_GPIO_NUM        5
#define VSYNC_GPIO_NUM    25
#define HREF_GPIO_NUM     23
#define PCLK_GPIO_NUM     22

#define BATTERY_PIN 33
#define JSON_DOC_SIZE 1024
#define PLATFORM_SYNC_INTERVAL_MS 5000

AsyncWebServer server(HTTP_SERVER_PORT);
AsyncWebSocket ws("/");

struct CameraStatus {
  bool streaming = true;
  String resolution = "640x480";
  int frameRate = 25;
  int battery = 100;
  bool connected = false;
} cameraStatus;

int streamClients = 0;
bool platformRegistered = false;

bool ensurePlatformRegistered() {
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }

  HTTPClient http;
  String url = String(SERVER_BASE) + "/api/robot/register";
  http.begin(url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<256> payload;
  payload["device_id"] = ROBOT_DEVICE_ID;
  payload["name"] = ROBOT_NAME;

  String out;
  serializeJson(payload, out);

  int code = http.POST(out);
  String body = http.getString();
  http.end();

  if (code == 200 || code == 409) {
    platformRegistered = true;
    return true;
  }

  Serial.printf("Platform register failed, code=%d body=%s\n", code, body.c_str());
  return false;
}

void applyPlatformCommand(const String& command, JsonObject target) {
  if (command.length() == 0 || command == "IDLE") {
    return;
  }

  Serial.printf("Platform command: %s\n", command.c_str());

  if (command == "PAUSE" || command == "STOP" || command == "HOLD_POSITION") {
    cameraStatus.streaming = false;
  } else if (command == "RESUME") {
    cameraStatus.streaming = true;
  } else if (command == "RESET") {
    cameraStatus.streaming = true;
    cameraStatus.frameRate = 25;
    cameraStatus.resolution = "640x480";
  } else if (command == "NAVIGATE") {
    float lat = target["lat"] | 0.0f;
    float lng = target["lng"] | 0.0f;
    Serial.printf("Navigate target received: lat=%.6f lng=%.6f\n", lat, lng);
  }
}

void syncPlatformHeartbeat() {
  if (WiFi.status() != WL_CONNECTED) {
    return;
  }
  if (!platformRegistered && !ensurePlatformRegistered()) {
    return;
  }

  HTTPClient http;
  String url = String(SERVER_BASE) + "/api/robot/heartbeat";
  http.begin(url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<512> payload;
  payload["device_id"] = ROBOT_DEVICE_ID;
  payload["lat"] = 0.0;
  payload["lng"] = 0.0;
  payload["battery"] = cameraStatus.battery;
  payload["status"] = cameraStatus.streaming ? "ONLINE" : "IDLE";
  JsonObject cfg = payload.createNestedObject("config");
  cfg["streaming"] = cameraStatus.streaming;
  cfg["resolution"] = cameraStatus.resolution;
  cfg["frame_rate"] = cameraStatus.frameRate;
  cfg["stream_clients"] = streamClients;

  String out;
  serializeJson(payload, out);

  int code = http.POST(out);
  String resp = http.getString();
  http.end();

  if (code == 403) {
    platformRegistered = false;
    ensurePlatformRegistered();
    return;
  }

  if (code != 200) {
    Serial.printf("Heartbeat failed, code=%d\n", code);
    return;
  }

  StaticJsonDocument<512> doc;
  DeserializationError err = deserializeJson(doc, resp);
  if (err) {
    Serial.printf("Heartbeat parse error: %s\n", err.c_str());
    return;
  }

  String command = doc["command"] | "IDLE";
  JsonObject target = doc["target"].as<JsonObject>();
  applyPlatformCommand(command, target);
}

bool setupCamera() {
  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer = LEDC_TIMER_0;
  config.pin_pwdn = PWDN_GPIO_NUM;
  config.pin_reset = RESET_GPIO_NUM;
  config.pin_xclk = XCLK_GPIO_NUM;
  config.pin_sscb_sda = SIOD_GPIO_NUM;
  config.pin_sscb_scl = SIOC_GPIO_NUM;
  config.pin_d7 = Y9_GPIO_NUM;
  config.pin_d6 = Y8_GPIO_NUM;
  config.pin_d5 = Y7_GPIO_NUM;
  config.pin_d4 = Y6_GPIO_NUM;
  config.pin_d3 = Y5_GPIO_NUM;
  config.pin_d2 = Y4_GPIO_NUM;
  config.pin_d1 = Y3_GPIO_NUM;
  config.pin_d0 = Y2_GPIO_NUM;
  config.pin_vsync = VSYNC_GPIO_NUM;
  config.pin_href = HREF_GPIO_NUM;
  config.pin_pclk = PCLK_GPIO_NUM;
  config.pixel_format = PIXFORMAT_JPEG;

  if (cameraStatus.resolution == "640x480") {
    config.frame_size = FRAMESIZE_VGA;
  } else if (cameraStatus.resolution == "320x240") {
    config.frame_size = FRAMESIZE_QVGA;
  } else if (cameraStatus.resolution == "1280x720") {
    config.frame_size = FRAMESIZE_SVGA;
  } else {
    config.frame_size = FRAMESIZE_VGA;
  }

  config.jpeg_quality = 12;
  config.fb_count = 2;

  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("Camera init failed with error 0x%x", err);
    return false;
  }

  Serial.println("Camera initialized successfully");
  return true;
}

int readBattery() {
  int batteryValue = analogRead(BATTERY_PIN);
  int batteryPercent = map(batteryValue, 1500, 2400, 0, 100);
  return constrain(batteryPercent, 0, 100);
}

void sendSocketMessage(const char* message) {
  ws.textAll(message);
}

void sendCameraStatus() {
  StaticJsonDocument<JSON_DOC_SIZE> doc;
  doc["type"] = "status";
  JsonObject data = doc.createNestedObject("data");
  data["connected"] = cameraStatus.connected;
  data["streaming"] = cameraStatus.streaming;
  data["resolution"] = cameraStatus.resolution;
  data["frameRate"] = cameraStatus.frameRate;
  data["battery"] = cameraStatus.battery;
  data["streamClients"] = streamClients;

  char buffer[JSON_DOC_SIZE];
  serializeJson(doc, buffer);
  sendSocketMessage(buffer);
}

void handleSocketEvent(AsyncWebSocket *serverRef, AsyncWebSocketClient *client,
                       AwsEventType type, void *arg, uint8_t *data, size_t len) {
  switch (type) {
    case WS_EVT_CONNECT: {
      Serial.printf("WebSocket client #%u connected from %s\n",
                    client->id(), client->remoteIP().toString().c_str());
      cameraStatus.connected = true;
      sendCameraStatus();
      break;
    }
    case WS_EVT_DISCONNECT: {
      Serial.printf("WebSocket client #%u disconnected\n", client->id());
      if (serverRef->count() == 0) {
        cameraStatus.connected = false;
      }
      break;
    }
    case WS_EVT_DATA: {
      AwsFrameInfo *info = (AwsFrameInfo*)arg;
      if (info->final && info->index == 0 && info->len == len && info->opcode == WS_TEXT) {
        String message;
        message.reserve(len + 1);
        for (size_t i = 0; i < len; ++i) {
          message += (char)data[i];
        }
        Serial.printf("Received command: %s\n", message.c_str());

        StaticJsonDocument<JSON_DOC_SIZE> doc;
        DeserializationError error = deserializeJson(doc, message);
        if (error) {
          Serial.printf("JSON parse error: %s\n", error.c_str());
          return;
        }

        String commandType = doc["type"];
        if (commandType == "status") {
          sendCameraStatus();
        } else if (commandType == "stream") {
          String action = doc["action"];
          if (action == "start") {
            cameraStatus.streaming = true;
            Serial.println("Streaming started");
          } else if (action == "stop") {
            cameraStatus.streaming = false;
            Serial.println("Streaming stopped");
          }
          sendCameraStatus();
        } else if (commandType == "config") {
          JsonObject config = doc["data"];
          if (config.containsKey("resolution")) {
            String resolution = config["resolution"];
            cameraStatus.resolution = resolution;
            Serial.printf("Resolution set to: %s\n", resolution.c_str());
            esp_camera_deinit();
            setupCamera();
          }

          if (config.containsKey("frameRate")) {
            int frameRate = config["frameRate"];
            cameraStatus.frameRate = constrain(frameRate, 1, 60);
            Serial.printf("Frame rate set to: %d FPS\n", cameraStatus.frameRate);
          }

          sendCameraStatus();
        }
      }
      break;
    }
    default:
      break;
  }
}

void serveStream(AsyncWebServerRequest *request) {
  Serial.println("Stream request received");
  streamClients++;

  request->sendContent("HTTP/1.1 200 OK\n");
  request->sendContent("Content-Type: multipart/x-mixed-replace; boundary=frame\n");
  request->sendContent("\n");

  while (cameraStatus.streaming && streamClients > 0) {
    camera_fb_t *fb = esp_camera_fb_get();
    if (fb) {
      request->sendContent("--frame\n");
      request->sendContent("Content-Type: image/jpeg\n");
      request->sendContent(String("Content-Length: ") + String(fb->len) + "\n");
      request->sendContent("\n");
      request->sendContent((const char *)fb->buf, fb->len);
      request->sendContent("\n");
      esp_camera_fb_return(fb);
      delay(1000 / cameraStatus.frameRate);
    }
  }

  streamClients--;
  Serial.println("Stream client disconnected");
}

void serveHome(AsyncWebServerRequest *request) {
  request->send(200, "text/plain", "ESP32 Camera Server\nStream URL: /stream\nWebSocket URL: ws://" + WiFi.localIP().toString() + ":" + String(WEBSOCKET_PORT));
}

int readWifiRssi() {
  return WiFi.RSSI();
}

void connectWifi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  Serial.print("Connecting to WiFi..");
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print('.');
    delay(1000);
  }

  Serial.println();
  Serial.print("WiFi connected. IP address: ");
  Serial.println(WiFi.localIP());

  if (!MDNS.begin("esp32-camera")) {
    Serial.println("Error setting up MDNS responder!");
  } else {
    Serial.println("mDNS responder started");
  }
}

void startServer() {
  ws.onEvent(handleSocketEvent);
  server.addHandler(&ws);
  server.on("/", HTTP_GET, serveHome);
  server.on("/stream", HTTP_GET, serveStream);
  server.begin();
  Serial.printf("HTTP server started on port %d\n", HTTP_SERVER_PORT);
  Serial.printf("WebSocket server started on port %d\n", WEBSOCKET_PORT);
}

void startOta() {
  ArduinoOTA.onStart([]() {
    String type;
    if (ArduinoOTA.getCommand() == U_FLASH) {
      type = "sketch";
    } else {
      type = "filesystem";
    }
    Serial.println("OTA Update started: " + type);
  });

  ArduinoOTA.onEnd([]() {
    Serial.println("OTA Update finished");
  });

  ArduinoOTA.onProgress([](unsigned int progress, unsigned int total) {
    Serial.printf("OTA Progress: %u%%\r", (progress / (total / 100)));
  });

  ArduinoOTA.onError([](ota_error_t error) {
    Serial.printf("OTA Error: %u\n", error);
    if (error == OTA_AUTH_ERROR) {
      Serial.println("OTA Auth Failed");
    } else if (error == OTA_BEGIN_ERROR) {
      Serial.println("OTA Begin Failed");
    } else if (error == OTA_CONNECT_ERROR) {
      Serial.println("OTA Connect Failed");
    } else if (error == OTA_RECEIVE_ERROR) {
      Serial.println("OTA Receive Failed");
    } else if (error == OTA_END_ERROR) {
      Serial.println("OTA End Failed");
    }
  });

  ArduinoOTA.begin();
  Serial.println("OTA Update initialized");
}

void setup() {
  Serial.begin(115200);
  Serial.println("ESP32CAM Controller initialized");

  if (!SPIFFS.begin(true)) {
    Serial.println("SPIFFS Mount Failed");
    return;
  }

  connectWifi();

  if (!setupCamera()) {
    return;
  }

  startServer();
  startOta();
  pinMode(BATTERY_PIN, INPUT);

  ensurePlatformRegistered();

  Serial.println("System ready!");
  Serial.printf("Stream URL: http://%s:%d/stream\n", WiFi.localIP().toString().c_str(), HTTP_SERVER_PORT);
  Serial.printf("WebSocket URL: ws://%s:%d\n", WiFi.localIP().toString().c_str(), WEBSOCKET_PORT);
}

void loop() {
  ArduinoOTA.handle();
  ws.cleanupClients();

  static unsigned long lastBatteryAt = 0;
  if (millis() - lastBatteryAt > 1000) {
    cameraStatus.battery = readBattery();
    lastBatteryAt = millis();
  }

  static unsigned long lastStatusAt = 0;
  if (millis() - lastStatusAt > PLATFORM_SYNC_INTERVAL_MS) {
    syncPlatformHeartbeat();
    sendCameraStatus();
    lastStatusAt = millis();
  }

  static unsigned long lastDebugAt = 0;
  if (millis() - lastDebugAt > 10000) {
    Serial.printf("Status: Streaming=%d, Resolution=%s, FPS=%d, Battery=%d%%, Clients=%d, WiFi=%ddBm\n",
                 cameraStatus.streaming,
                 cameraStatus.resolution.c_str(),
                 cameraStatus.frameRate,
                 cameraStatus.battery,
                 streamClients,
                 readWifiRssi());
    lastDebugAt = millis();
  }

  delay(10);
}
