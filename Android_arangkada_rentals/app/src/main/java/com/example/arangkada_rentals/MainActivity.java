package com.example.arangkada_rentals;

import android.content.Intent;
import android.os.Bundle;
import android.util.Patterns;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.arangkada_rentals.Dashboard.AdminDashboardActivity;
import com.example.arangkada_rentals.Dashboard.ClientDashboardActivity;
import com.example.arangkada_rentals.Dashboard.DriverDashboardActivity;
import com.example.arangkada_rentals.Dashboard.StaffDashboardActivity;
import com.example.arangkada_rentals.Dashboard.SuperAdminDashboardActivity;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;

public class MainActivity extends AppCompatActivity {

    private EditText etEmail, etPassword;
    private Button btnLogin;

    private static final String LOGIN_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/login.php";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        etEmail = findViewById(R.id.etEmail);
        etPassword = findViewById(R.id.etPassword);
        btnLogin = findViewById(R.id.btnLogin);

        btnLogin.setOnClickListener(view -> validateLogin());
    }

    private void validateLogin() {
        String email = etEmail.getText().toString().trim();
        String password = etPassword.getText().toString().trim();

        if (email.isEmpty()) {
            etEmail.setError("Email is required");
            etEmail.requestFocus();
            return;
        }

        if (!Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            etEmail.setError("Enter a valid email");
            etEmail.requestFocus();
            return;
        }

        if (password.isEmpty()) {
            etPassword.setError("Password is required");
            etPassword.requestFocus();
            return;
        }

        loginUser(email, password);
    }

    private void loginUser(String email, String password) {
        new Thread(() -> {
            try {
                URL url = new URL(LOGIN_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setDoInput(true);
                conn.setDoOutput(true);

                String postData = "username=" + URLEncoder.encode(email, "UTF-8") +
                        "&password=" + URLEncoder.encode(password, "UTF-8");

                OutputStream os = conn.getOutputStream();
                BufferedWriter writer = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));
                writer.write(postData);
                writer.flush();
                writer.close();
                os.close();

                int responseCode = conn.getResponseCode();

                BufferedReader reader = new BufferedReader(new InputStreamReader(
                        (responseCode == 200) ? conn.getInputStream() : conn.getErrorStream()
                ));

                StringBuilder result = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    result.append(line);
                }
                reader.close();

                String response = result.toString();

                runOnUiThread(() -> handleLoginResponse(response));

            } catch (Exception e) {
                e.printStackTrace();
                runOnUiThread(() ->
                        Toast.makeText(MainActivity.this, "Error: " + e.getMessage(), Toast.LENGTH_LONG).show());
            }
        }).start();
    }

    private void handleLoginResponse(String response) {
        // Debug log of raw response
        System.out.println("Server response: " + response);

        if (response == null || response.trim().isEmpty()) {
            Toast.makeText(this, "Empty response from server", Toast.LENGTH_LONG).show();
            return;
        }

        try {
            JSONObject jsonObject = new JSONObject(response);

            String status = jsonObject.optString("status");
            String role = jsonObject.optString("role");
            String message = jsonObject.optString("message");

            if ("success".equalsIgnoreCase(status)) {
                Toast.makeText(this, "✅ " + message, Toast.LENGTH_SHORT).show();

                Intent intent;
                switch (role.toLowerCase()) {
                    case "superadmin":
                        intent = new Intent(MainActivity.this, SuperAdminDashboardActivity.class);
                        break;
                    case "admin":
                        intent = new Intent(MainActivity.this, AdminDashboardActivity.class);
                        break;
                    case "staff":
                        intent = new Intent(MainActivity.this, StaffDashboardActivity.class);
                        break;
                    case "driver":
                        intent = new Intent(MainActivity.this, DriverDashboardActivity.class);
                        break;
                    case "client":
                    case "customer":
                        intent = new Intent(MainActivity.this, ClientDashboardActivity.class);
                        break;
                    default:
                        Toast.makeText(this, "Unknown user role: " + role, Toast.LENGTH_LONG).show();
                        return;
                }
                startActivity(intent);
                finish();

            } else if ("verify".equalsIgnoreCase(status) || response.toLowerCase().contains("verify")) {
                Toast.makeText(this, "⚠️ Please verify your email", Toast.LENGTH_LONG).show();
            } else {
                Toast.makeText(this, "❌ " + message, Toast.LENGTH_SHORT).show();
            }
        } catch (Exception e) {
            e.printStackTrace();
            Toast.makeText(this, "Invalid response from server", Toast.LENGTH_LONG).show();
        }
    }
}
