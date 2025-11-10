#include "DHT.h"
#include <WiFi.h>
#include <HTTPClient.h>

#define DHTPIN 13            // Pin data DHT22 dihubungkan ke GPIO 4
#define DHTTYPE DHT22       // Tipe sensor DHT22

DHT dht(DHTPIN, DHTTYPE);

// Ganti dengan WiFi kamu
const char* ssid = "gratisan";
const char* password = "12345678";

// Ganti dengan alamat server / IP lokal XAMPP kamu
const char* server = "http://192.168.1.5/DashboardSuhu/kirimdata.php";

void setup() {
  Serial.begin(115200);
  dht.begin();

  // Koneksi ke WiFi
  WiFi.begin(ssid, password);
  Serial.print("Menghubungkan ke WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.println("Tersambung ke WiFi!");
}

void loop() {
  // Membaca kelembapan dan suhu
  float humi = dht.readHumidity();
  float temp = dht.readTemperature();

  // Validasi pembacaan sensor
  if (isnan(humi) || isnan(temp)) {
    Serial.println("Gagal membaca dari sensor DHT!");
    return;
  }

  // Menampilkan ke Serial Monitor
  Serial.println("Kelembapan: " + String(humi, 2) + " | Suhu: " + String(temp, 2));

  // Kirim ke server jika WiFi terhubung
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;

    // Buat URL lengkap dengan parameter suhu dan kelembapan
    String url = String(server) + "?suhu=" + String(temp, 2) + "&humi=" + String(humi, 2);

    http.begin(url);  // Inisialisasi koneksi
    int httpResponseCode = http.GET();  // Kirim GET request

    if (httpResponseCode > 0) {
      String response = http.getString();  // Baca respons dari server
      Serial.println("Respon Server: " + response);
    } else {
      Serial.println("Gagal mengirim data. Error: " + String(httpResponseCode));
    }

    http.end(); // Akhiri koneksi
  } else {
    Serial.println("Tidak ada koneksi WiFi!");
  }

  delay(1000);
}
