package com.example.arangkada_rentals;

import android.content.Intent;
import android.os.Bundle;
import android.util.Patterns;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.arangkada_rentals.AdminDashboardActivity;
import com.example.arangkada_rentals.ClientDashboardActivity;
//import com.example.arangkada_rentals.DriverDashboardActivity;
//import com.example.arangkada_rentals.StaffDashboardActivity;
import com.example.arangkada_rentals.SuperAdminDashboardActivity;

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

    private EditText etUsernameEmail, etPassword;
    private Button btnLogin;
    private ProgressBar progressBar;
    private TextView tvRegister;

    // Update this to match your server IP address
    private static final String LOGIN_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/login.php";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        etUsernameEmail = findViewById(R.id.etUsernameEmail);
        etPassword = findViewById(R.id.etPassword);
        btnLogin = findViewById(R.id.btnLogin);
        progressBar = findViewById(R.id.progressBar);
        tvRegister = findViewById(R.id.tvRegister);

        btnLogin.setOnClickListener(view -> validateLogin());
        tvRegister.setOnClickListener(view -> {
            startActivity(new Intent(MainActivity.this, RegisterActivity.class));
        });
    }

    private void validateLogin() {
        String usernameEmail = etUsernameEmail.getText().toString().trim();
        String password = etPassword.getText().toString().trim();

        if (usernameEmail.isEmpty()) {
            etUsernameEmail.setError("Username or Email is required");
            etUsernameEmail.requestFocus();
            return;
        }

        // If input contains @ symbol, validate as email
        if (usernameEmail.contains("@")) {
            if (!Patterns.EMAIL_ADDRESS.matcher(usernameEmail).matches()) {
                etUsernameEmail.setError("Enter a valid email");
                etUsernameEmail.requestFocus();
                return;
            }
        } else {
            // Validate as username
            if (usernameEmail.length() < 3) {
                etUsernameEmail.setError("Username must be at least 3 characters");
                etUsernameEmail.requestFocus();
                return;
            }
        }

        if (password.isEmpty()) {
            etPassword.setError("Password is required");
            etPassword.requestFocus();
            return;
        }

        // Show progress bar and disable login button
        progressBar.setVisibility(View.VISIBLE);
        btnLogin.setEnabled(false);
        
        loginUser(usernameEmail, password);
    }

    private void loginUser(String usernameEmail, String password) {
        new Thread(() -> {
            try {
                URL url = new URL(LOGIN_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setDoInput(true);
                conn.setDoOutput(true);

                // Send both username and email fields
                String postData = "username_email=" + URLEncoder.encode(usernameEmail, "UTF-8") +
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

                runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    btnLogin.setEnabled(true);
                    handleLoginResponse(response);
                });

            } catch (Exception e) {
                e.printStackTrace();
                runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    btnLogin.setEnabled(true);
                    Toast.makeText(MainActivity.this, "Error: " + e.getMessage(), Toast.LENGTH_LONG).show();
                });
            }
        }).start();
    }

    private void handleLoginResponse(String response) {
        try {
            JSONObject jsonObject = new JSONObject(response);
            String status = jsonObject.getString("status");
            String message = jsonObject.getString("message");

            if ("success".equals(status)) {
                JSONObject user = jsonObject.getJSONObject("user");
                String role = user.getString("role");

                // Show success message
                Toast.makeText(this, "✅ " + message, Toast.LENGTH_SHORT).show();

                // Create intent based on role
                Intent intent;
                switch (role.toLowerCase()) {
                    case "superadmin":
                        intent = new Intent(this, SuperAdminDashboardActivity.class);
                        break;
                    case "admin":
                        intent = new Intent(this, AdminDashboardActivity.class);
                        break;
                    case "client":
                        intent = new Intent(this, ClientDashboardActivity.class);
                        break;
                    default:
                        Toast.makeText(this, "Unknown user role: " + role, Toast.LENGTH_LONG).show();
                        return;
                }

                // Pass user data to dashboard
                intent.putExtra("user_id", user.getString("id"));
                intent.putExtra("username", user.getString("username"));
                intent.putExtra("role", role);

                startActivity(intent);
                finish(); // Close login activity
            } else {
                Toast.makeText(this, "❌ " + message, Toast.LENGTH_LONG).show();
            }
        } catch (Exception e) {
            e.printStackTrace();
            Toast.makeText(this, "Error parsing response: " + e.getMessage(), Toast.LENGTH_LONG).show();
        }
    }
}
