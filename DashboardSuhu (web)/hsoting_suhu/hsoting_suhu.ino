#include "DHT.h"
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClient.h>
#include <ArduinoJson.h>

#define DHTPIN 4
#define DHTTYPE DHT22

DHT dht(DHTPIN, DHTTYPE);

// Konfigurasi WiFi
const char* ssid = "gratisan";
const char* password = "krtmi2025";

// Konfigurasi Server
const char* server = "http://projectfarhan.infinityfreeapp.com/kirimdata.php";
const char* checkServer = "http://projectfarhan.infinityfreeapp.com/check.php";

// Interval waktu
const long sendInterval = 10000; // Kirim data setiap 10 detik
const long readInterval = 2000;  // Baca sensor setiap 2 detik
const long wifiRetryInterval = 30000; // Coba ulang WiFi setiap 30 detik jika gagal

// Variabel status
unsigned long lastSendTime = 0;
unsigned long lastReadTime = 0;
unsigned long lastWifiAttempt = 0;
float lastTemp = 0;
float lastHumi = 0;
bool wifiConnected = false;
int sendAttemptCount = 0;

void setup() {
  Serial.begin(115200);
  dht.begin();
  
  // Mulai dengan mencoba koneksi WiFi
  connectToWiFi();
  
  Serial.println("\nSystem initialized");
  Serial.println("------------------");
}

void loop() {
  unsigned long currentMillis = millis();

  // Baca sensor secara berkala
  if (currentMillis - lastReadTime >= readInterval) {
    readSensor();
    lastReadTime = currentMillis;
  }

  // Kirim data secara berkala
  if (currentMillis - lastSendTime >= sendInterval) {
    if (WiFi.status() == WL_CONNECTED) {
      sendSensorData();
      lastSendTime = currentMillis;
    } else {
      Serial.println("Skipping send - WiFi disconnected");
    }
  }

  // Coba ulang WiFi jika terputus
  if (WiFi.status() != WL_CONNECTED && currentMillis - lastWifiAttempt >= wifiRetryInterval) {
    connectToWiFi();
    lastWifiAttempt = currentMillis;
  }
}

void readSensor() {
  float humi = dht.readHumidity();
  float temp = dht.readTemperature();

  if (!isnan(humi) && !isnan(temp)) {
    lastTemp = temp;
    lastHumi = humi;
    Serial.printf("[SENSOR] Temp=%.1f°C, Humi=%.1f%%\n", temp, humi);
  } else {
    Serial.println("[SENSOR] Error reading data");
  }
}

void connectToWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;

  Serial.println("[WiFi] Connecting...");
  WiFi.disconnect(true);
  delay(100);
  WiFi.begin(ssid, password);

  unsigned long startTime = millis();
  bool printedProgress = false;
  
  while (WiFi.status() != WL_CONNECTED && millis() - startTime < 15000) {
    delay(500);
    if (!printedProgress) {
      Serial.print(".");
      printedProgress = true;
    } else {
      printedProgress = false;
    }
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n[WiFi] Connected!");
    Serial.print("[WiFi] IP Address: ");
    Serial.println(WiFi.localIP());
    wifiConnected = true;
  } else {
    Serial.println("\n[WiFi] Connection Failed");
    wifiConnected = false;
  }
}

void sendSensorData() {
  sendAttemptCount++;
  Serial.printf("\n[HTTP] Attempt #%d to send data\n", sendAttemptCount);
  
  // Buat URL dengan parameter
  String url = String(server) + "?suhu=" + String(lastTemp, 1) + "&humi=" + String(lastHumi, 1);
  Serial.println("[HTTP] URL: " + url);
  
  WiFiClient client;
  HTTPClient http;

  // Konfigurasi HTTP
  http.begin(client, url);
  http.setTimeout(10000); // Timeout 10 detik
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.addHeader("Cache-Control", "no-cache");

  // Kirim data
  int httpCode = http.GET();
  String payload = http.getString();
  http.end();

  // Proses respons
  if (httpCode > 0) {
    Serial.printf("[HTTP] Response code: %d\n", httpCode);
    
    if (httpCode == HTTP_CODE_OK) {
      // Coba parsing JSON
      DynamicJsonDocument doc(256);
      DeserializationError error = deserializeJson(doc, payload);
      
      if (!error) {
        // Respons JSON valid
        const char* status = doc["status"];
        Serial.printf("[SERVER] Status: %s\n", status);
        
        if (strcmp(status, "success") == 0) {
          Serial.println("[SERVER] Data saved successfully");
          verifyDataInDatabase(); // Verifikasi data benar-benar tersimpan
          return;
        }
      } else {
        // Tangani respons non-JSON (InfinityFree security)
        Serial.println("[SERVER] Non-JSON response detected");
        Serial.println("[SERVER] Payload: " + payload.substring(0, 100) + "...");
        
        // Verifikasi data tetap tersimpan
        verifyDataInDatabase();
        return;
      }
    }
  } else {
    Serial.printf("[HTTP] Error: %s\n", http.errorToString(httpCode).c_str());
  }

  // Jika sampai di sini berarti ada masalah
  Serial.println("[WARNING] Data saving not confirmed");
  delay(2000); // Jeda sebelum verifikasi ulang
  verifyDataInDatabase();
}

void verifyDataInDatabase() {
  Serial.println("[VERIFY] Checking last data in database...");
  
  WiFiClient client;
  HTTPClient http;

  http.begin(client, checkServer);
  http.setTimeout(8000);
  
  int httpCode = http.GET();
  
  if (httpCode == HTTP_CODE_OK) {
    String payload = http.getString();
    
    DynamicJsonDocument doc(256);
    DeserializationError error = deserializeJson(doc, payload);
    
    if (!error) {
      float dbTemp = doc["suhu"];
      float dbHumi = doc["kelembaban"];
      
      Serial.printf("[VERIFY] Last record in DB: %.1f°C, %.1f%%\n", dbTemp, dbHumi);
      
      // Bandingkan dengan data terakhir yang dikirim
      if (fabs(dbTemp - lastTemp) < 0.5 && fabs(dbHumi - lastHumi) < 0.5) {
        Serial.println("[VERIFY] Data matches! Confirmed saved.");
      } else {
        Serial.println("[VERIFY] Data mismatch! May not be saved.");
      }
    } else {
      Serial.println("[VERIFY] Error parsing verification response");
    }
  } else {
    Serial.printf("[VERIFY] Error: %d - %s\n", httpCode, http.errorToString(httpCode).c_str());
  }
  
  http.end();
}