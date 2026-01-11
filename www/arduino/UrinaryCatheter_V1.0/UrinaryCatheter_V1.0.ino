#include <Servo.h>
#include <WiFiS3.h>
#include <Wire.h>
#include <WiFiClient.h>
#include <HttpClient.h>

// ===== Function Prototypes =====
void fn_callhttpget(String strParameter);
void fn_openLineMessage(String strParameter);
String getUniqueKeyFromMAC();

// ############## [SECTION : WIFI - HOME] ##############
// const char* ssid = "KHUM KLOW_HOME47_2.4G";
// const char* password = "Ktp410983";
// const char* HOST_NAME = "192.168.0.155";
// const int HTTP_PORT = 80;

// ############## [SECTION : WIFI - MOBILE] ##############
const char* ssid = "Tongtang";
const char* password = "Bluetong12345";
const char* HOST_NAME = "10.14.196.90";
const int HTTP_PORT = 80;

WiFiClient client;

int status = WL_IDLE_STATUS;

String HTTP_METHOD = "GET";
String PATH_NAME = "/UrinaryCatheter/logflow.php";
String PATH_NAME_ALERT = "/UrinaryCatheter/trigger.php";
String cby = "arduino_r4_wifi";
int iDelayLoop = 10000; // Loop delay (ms)

String strParam = "";
// ############## [SECTION : WIFI] ##############

// ############## [SECTION : FLOW & SERVO] ##############
volatile uint32_t pulseA = 0;
volatile uint32_t pulseB = 0;

// ############## ALERT LOW LEVEL ##############
const int sFlowAvg = 20;
const int eFlowAvg = 500;

const int servoPin = 9;
const int FLOW_PIN_A = 2;
const int FLOW_PIN_B = 3;

const float PULSE_PER_LITER = 5880.0;
const float PULSE_TO_ML = 1000.0 / PULSE_PER_LITER; // ≈ 0.170 ml/pulse
const float FLOW_MIN_THRESHOLD = 25.0;
// ############## [SECTION : FLOW & SERVO] ##############

Servo myServo;

const unsigned long NO_FLOW_INTERVAL = 15UL * 60UL * 1000UL;  // 5 minutes

unsigned long lastFlowDetectedTime = 0;
unsigned long nextServoTriggerTime = 0;

void ISR_A() { pulseA++; }
void ISR_B() { pulseB++; }

void setup() {
  Serial.begin(9600);

  delay(1000);

  // ✅ Only initialize Serial once
  Serial.println("Initializing system...");

  // ✅ Check WiFi module
  if (WiFi.status() == WL_NO_MODULE) {
    Serial.println("Communication with WiFi module failed!");
    while (true);
  }

  // ✅ Connect to WiFi
  Serial.print("Connecting to WiFi");
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.print("Connected to SSID: ");
  Serial.println(ssid);
  Serial.println("WiFi Connected!");

  // Setup pins
  pinMode(FLOW_PIN_A, INPUT_PULLUP);
  pinMode(FLOW_PIN_B, INPUT_PULLUP);

  attachInterrupt(digitalPinToInterrupt(FLOW_PIN_A), ISR_A, RISING);
  attachInterrupt(digitalPinToInterrupt(FLOW_PIN_B), ISR_B, RISING);

  myServo.attach(servoPin);
  myServo.write(150); // Neutral
  Serial.println("Servo initialized at 150° [STOP]");

  unsigned long now = millis();
  lastFlowDetectedTime = now;
  nextServoTriggerTime = now + NO_FLOW_INTERVAL;
}

void loop() {
  
  unsigned long loopStart = millis();

  Serial.println("------------ START LOOP -----------");

  const float intervalSec = 2.0;
  uint32_t startA, startB, endA, endB;

  noInterrupts();
  startA = pulseA;
  startB = pulseB;
  interrupts();

  delay(2000); // Measurement interval

  noInterrupts();
  endA = pulseA;
  endB = pulseB;
  interrupts();

  float deltaA = endA - startA;
  float deltaB = endB - startB;
  float avgDelta = (deltaA + deltaB) / 2.0;

  // ✅ Compute flow rate in ml/hour
  float flowA_mlph = (deltaA * PULSE_TO_ML) * (3600.0 / intervalSec);
  float flowB_mlph = (deltaB * PULSE_TO_ML) * (3600.0 / intervalSec);
  float avgflow_mlph = (flowA_mlph + flowB_mlph)  / 2.0;

  String uniqueKey = getUniqueKeyFromMAC();
  strParam = "?pulseA=" + String(avgDelta, 2) +
             "&flowA=" + String(avgflow_mlph, 2) +
             "&logBy=" + cby +
             "&machineKey=" + uniqueKey;

  String direction;
  if ( deltaA > 0 && deltaA > deltaB ) {
      direction = "Flowing forward (A ➝ B)";
      fn_callhttpget(strParam);
      lastFlowDetectedTime = millis();
      nextServoTriggerTime = lastFlowDetectedTime + NO_FLOW_INTERVAL;
      if ((avgflow_mlph >= sFlowAvg) && (avgflow_mlph <= eFlowAvg)) {
        Serial.print("Flow low between ");Serial.print(sFlowAvg);Serial.print(" and ");Serial.println(eFlowAvg);
        fn_openLineMessage("low");
      }
  } else if ( deltaB > 0 && deltaB > deltaA ) {
      direction = "Reflux (B ➝ A)";
      fn_callhttpget(strParam);
      lastFlowDetectedTime = millis();
      nextServoTriggerTime = lastFlowDetectedTime + NO_FLOW_INTERVAL;
  } else {
      direction = "No Flow";
  }

  // ✅ No-flow detection & servo trigger
  if (deltaA == 0 && deltaB == 0 && millis() >= nextServoTriggerTime) {
    Serial.println("No flow for 5 mins >> Servo activates");

    myServo.write(45); // Squeeze
    Serial.println("Servo: 45° [Squeeze]");
    delay(5000);

    myServo.write(150); // Loosen
    Serial.println("Servo: 150° [Loosen]");

    nextServoTriggerTime = millis() + NO_FLOW_INTERVAL;

    noInterrupts();
    pulseA = 0;
    pulseB = 0;
    interrupts();

    delay(2000);

    noInterrupts();
    uint32_t afterPulseA = pulseA;
    uint32_t afterPulseB = pulseB;
    interrupts();

    float flowA_ml = afterPulseA * PULSE_TO_ML;
    float flowB_ml = afterPulseB * PULSE_TO_ML;

    Serial.println("Check the flow after the servo motor operates...");
    Serial.print("Sensor A: "); Serial.print(flowA_ml, 2); Serial.println(" ml");
    Serial.print("Sensor B: "); Serial.print(flowB_ml, 2); Serial.println(" ml");

    if ((flowA_ml < FLOW_MIN_THRESHOLD) && (flowB_ml < FLOW_MIN_THRESHOLD)) {
      Serial.println("No flow or flow below 25ml after Servo operation.");
      fn_openLineMessage("blockage");
    } else {
      Serial.println("Flow detected after servo motor is activated.");
    }

    myServo.attach(servoPin);
    return;
  }

  Serial.print("Sensor A: "); Serial.print(deltaA); Serial.print(" pulses");
  Serial.print(" | Flow A: "); Serial.print(flowA_mlph, 2); Serial.println(" ml/hour");
  Serial.print("Sensor B: "); Serial.print(deltaB); Serial.print(" pulses");
  Serial.print(" | Flow B: "); Serial.print(flowB_mlph, 2); Serial.println(" ml/hour");
  Serial.print(" => "); Serial.println(direction);
  Serial.println("------------ END LOOP -----------");

  unsigned long loopDuration = millis() - loopStart;
  if (loopDuration < iDelayLoop) {
    delay(iDelayLoop - loopDuration);
  }
}

/* -------------------------------------------------------------------------- */
void fn_callhttpget(String strParameter) {

  Serial.println("Call : fn_callhttpget : Start connection to server...");

  String request = HTTP_METHOD + " " + PATH_NAME + strParameter + " HTTP/1.1";

  if (client.connect(HOST_NAME, HTTP_PORT)) {
    Serial.println("Connected to server");

    client.println(request);
    client.println("Host: " + String(HOST_NAME));
    client.println("Connection: close");
    client.println();

    // DEBUG LOG
    Serial.println("---- HTTP REQUEST ----");
    Serial.println(request);
    Serial.println("Host: " + String(HOST_NAME));
    Serial.println("Connection: close");
    Serial.println("----------------------");
  } else {
    Serial.println("Connection failed");
  }

}

/* -------------------------------------------------------------------------- */
void fn_openLineMessage(String strParameter) {

  Serial.println("Call : fn_openLineMessage : Start connected to server for alert");

  String url = PATH_NAME_ALERT + "?param=" + strParameter;
  String request = String("GET ") + url + " HTTP/1.1\r\n";

  Serial.print("Request: ");Serial.println(request);

  if (client.connect(HOST_NAME, HTTP_PORT)) {
    Serial.println("Connected to server");

    client.print(request);
    client.print("Host: ");
    client.print(HOST_NAME);
    client.print("\r\n");
    client.print("Connection: close\r\n\r\n");
    client.stop();

    // DEBUG
    Serial.println("---- HTTP REQUEST ----");
    Serial.print(request);
    Serial.println("Host: " + String(HOST_NAME));
    Serial.println("Connection: close");
    Serial.println("----------------------");

  } else {
    Serial.println("Connection failed");
  }

}

/* -------------------------------------------------------------------------- */
String getUniqueKeyFromMAC() {
  byte mac[6];
  WiFi.macAddress(mac);
  String uniqueKey = "";
  for (int i = 0; i < 6; i++) {
    if (mac[i] < 16) uniqueKey += "0";
    uniqueKey += String(mac[i], HEX);
  }
  uniqueKey.toUpperCase();
  return uniqueKey;
}
