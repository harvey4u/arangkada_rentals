package com.example.arangkada_rentals;

import android.content.Intent;
import android.os.Bundle;
import android.util.Patterns;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.RadioButton;
import android.widget.RadioGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;

public class RegisterActivity extends AppCompatActivity {

    private EditText etUsername, etEmail, etPassword, etConfirmPassword;
    private RadioGroup rgRole;
    private RadioButton rbClient, rbAdmin;
    private Button btnRegister;
    private ProgressBar progressBar;
    private TextView tvLogin;

    private static final String REGISTER_URL = "http://10.0.2.2/ARANGKADA/arangkada_rentals/api/register.php";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        // Initialize views
        etUsername = findViewById(R.id.etUsername);
        etEmail = findViewById(R.id.etEmail);
        etPassword = findViewById(R.id.etPassword);
        etConfirmPassword = findViewById(R.id.etConfirmPassword);
        rgRole = findViewById(R.id.rgRole);
        rbClient = findViewById(R.id.rbClient);
        rbAdmin = findViewById(R.id.rbAdmin);
        btnRegister = findViewById(R.id.btnRegister);
        progressBar = findViewById(R.id.progressBar);
        tvLogin = findViewById(R.id.tvLogin);

        // Set click listeners
        btnRegister.setOnClickListener(view -> validateForm());
        tvLogin.setOnClickListener(view -> {
            startActivity(new Intent(RegisterActivity.this, MainActivity.class));
            finish();
        });
    }

    private void validateForm() {
        String username = etUsername.getText().toString().trim();
        String email = etEmail.getText().toString().trim();
        String password = etPassword.getText().toString().trim();
        String confirmPassword = etConfirmPassword.getText().toString().trim();
        String role = rbAdmin.isChecked() ? "admin" : "client";

        // Validate username
        if (username.isEmpty()) {
            etUsername.setError("Username is required");
            etUsername.requestFocus();
            return;
        }

        if (username.length() < 3) {
            etUsername.setError("Username must be at least 3 characters");
            etUsername.requestFocus();
            return;
        }

        // Validate email
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

        // Validate password
        if (password.isEmpty()) {
            etPassword.setError("Password is required");
            etPassword.requestFocus();
            return;
        }

        if (password.length() < 6) {
            etPassword.setError("Password must be at least 6 characters");
            etPassword.requestFocus();
            return;
        }

        // Validate confirm password
        if (confirmPassword.isEmpty()) {
            etConfirmPassword.setError("Please confirm your password");
            etConfirmPassword.requestFocus();
            return;
        }

        if (!password.equals(confirmPassword)) {
            etConfirmPassword.setError("Passwords do not match");
            etConfirmPassword.requestFocus();
            return;
        }

        // Show progress and disable button
        progressBar.setVisibility(View.VISIBLE);
        btnRegister.setEnabled(false);

        // Proceed with registration
        registerUser(username, email, password, role);
    }

    private void registerUser(String username, String email, String password, String role) {
        new Thread(() -> {
            try {
                URL url = new URL(REGISTER_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setDoInput(true);
                conn.setDoOutput(true);

                String postData = "username=" + URLEncoder.encode(username, "UTF-8") +
                        "&email=" + URLEncoder.encode(email, "UTF-8") +
                        "&password=" + URLEncoder.encode(password, "UTF-8") +
                        "&role=" + URLEncoder.encode(role, "UTF-8");

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
                    btnRegister.setEnabled(true);
                    handleRegisterResponse(response);
                });

            } catch (Exception e) {
                e.printStackTrace();
                runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    btnRegister.setEnabled(true);
                    Toast.makeText(RegisterActivity.this, "Error: " + e.getMessage(), Toast.LENGTH_LONG).show();
                });
            }
        }).start();
    }

    private void handleRegisterResponse(String response) {
        try {
            JSONObject jsonObject = new JSONObject(response);
            String status = jsonObject.getString("status");
            String message = jsonObject.getString("message");

            if ("success".equals(status)) {
                Toast.makeText(this, "✅ " + message, Toast.LENGTH_LONG).show();
                
                // Start verification activity
                Intent intent = new Intent(RegisterActivity.this, VerificationActivity.class);
                intent.putExtra("email", etEmail.getText().toString().trim());
                startActivity(intent);
                finish();
            } else {
                Toast.makeText(this, "❌ " + message, Toast.LENGTH_LONG).show();
            }
        } catch (Exception e) {
            Toast.makeText(this, "Error parsing response", Toast.LENGTH_LONG).show();
        }
    }
} 