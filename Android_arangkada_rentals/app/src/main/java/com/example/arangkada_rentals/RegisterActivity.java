package com.example.arangkada_rentals;

import android.content.Intent;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
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
import androidx.appcompat.app.AlertDialog;

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

    private static final String TAG = "RegisterActivity";
    private static final String REGISTER_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/register.php";
    private static final int CONNECTION_TIMEOUT = 30000; // 30 seconds
    private static final int READ_TIMEOUT = 30000; // 30 seconds

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        // Initialize views
        initializeViews();
        
        // Set click listeners
        setClickListeners();
    }

    private void initializeViews() {
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

        // Set client as default role
        rbClient.setChecked(true);
    }

    private void setClickListeners() {
        btnRegister.setOnClickListener(view -> {
            if (!isNetworkAvailable()) {
                showErrorDialog("No Internet Connection", 
                    "Please check your internet connection and try again.");
                return;
            }
            validateForm();
        });

        tvLogin.setOnClickListener(view -> {
            startActivity(new Intent(RegisterActivity.this, MainActivity.class));
            finish();
        });
    }

    private boolean isNetworkAvailable() {
        ConnectivityManager connectivityManager = (ConnectivityManager) getSystemService(CONNECTIVITY_SERVICE);
        NetworkInfo activeNetworkInfo = connectivityManager.getActiveNetworkInfo();
        return activeNetworkInfo != null && activeNetworkInfo.isConnected();
    }

    private void validateForm() {
        // Get form data and trim whitespace
        String username = etUsername.getText().toString().trim();
        String email = etEmail.getText().toString().trim();
        String password = etPassword.getText().toString().trim();
        String confirmPassword = etConfirmPassword.getText().toString().trim();
        String role = rbAdmin.isChecked() ? "admin" : "client";

        // Clear previous errors
        etUsername.setError(null);
        etEmail.setError(null);
        etPassword.setError(null);
        etConfirmPassword.setError(null);

        // Log validation attempt
        android.util.Log.d(TAG, "Validating form data:");
        android.util.Log.d(TAG, "Username: " + (username.isEmpty() ? "empty" : "provided"));
        android.util.Log.d(TAG, "Email: " + (email.isEmpty() ? "empty" : "provided"));
        android.util.Log.d(TAG, "Password: " + (password.isEmpty() ? "empty" : "provided"));
        android.util.Log.d(TAG, "Confirm Password: " + (confirmPassword.isEmpty() ? "empty" : "provided"));
        android.util.Log.d(TAG, "Role: " + role);

        // Check for empty fields
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

        if (password.length() < 6) {
            etPassword.setError("Password must be at least 6 characters");
            etPassword.requestFocus();
            return;
        }

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

        // Double check that no field is empty before proceeding
        if (username.isEmpty() || email.isEmpty() || password.isEmpty() || confirmPassword.isEmpty() || role.isEmpty()) {
            showErrorDialog("Validation Error", "All fields are required");
            return;
        }

        // Show progress and disable button
        setLoading(true);

        // Log successful validation
        android.util.Log.d(TAG, "Form validation passed, proceeding with registration");

        // Proceed with registration
        registerUser(username, email, password, role);
    }

    private void setLoading(boolean isLoading) {
        progressBar.setVisibility(isLoading ? View.VISIBLE : View.GONE);
        btnRegister.setEnabled(!isLoading);
        etUsername.setEnabled(!isLoading);
        etEmail.setEnabled(!isLoading);
        etPassword.setEnabled(!isLoading);
        etConfirmPassword.setEnabled(!isLoading);
        rgRole.setEnabled(!isLoading);
    }

    private void registerUser(String username, String email, String password, String role) {
        new Thread(() -> {
            HttpURLConnection conn = null;
            try {
                // Log network attempt
                android.util.Log.d(TAG, "Starting registration process...");
                android.util.Log.d(TAG, "Connecting to: " + REGISTER_URL);

                URL url = new URL(REGISTER_URL);
                conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setDoInput(true);
                conn.setDoOutput(true);
                conn.setConnectTimeout(CONNECTION_TIMEOUT);
                conn.setReadTimeout(READ_TIMEOUT);
                conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
                conn.setRequestProperty("Accept", "application/json");

                // Create the POST data string with URL encoding
                StringBuilder postDataBuilder = new StringBuilder();
                postDataBuilder.append(URLEncoder.encode("username", "UTF-8"))
                    .append("=")
                    .append(URLEncoder.encode(username, "UTF-8"));
                postDataBuilder.append("&")
                    .append(URLEncoder.encode("email", "UTF-8"))
                    .append("=")
                    .append(URLEncoder.encode(email, "UTF-8"));
                postDataBuilder.append("&")
                    .append(URLEncoder.encode("password", "UTF-8"))
                    .append("=")
                    .append(URLEncoder.encode(password, "UTF-8"));
                postDataBuilder.append("&")
                    .append(URLEncoder.encode("role", "UTF-8"))
                    .append("=")
                    .append(URLEncoder.encode(role, "UTF-8"));


                String postData = postDataBuilder.toString();

                // Log request details
                android.util.Log.d(TAG, "Request Headers:");
                android.util.Log.d(TAG, "Content-Type: " + conn.getRequestProperty("Content-Type"));
                android.util.Log.d(TAG, "Accept: " + conn.getRequestProperty("Accept"));
                android.util.Log.d(TAG, "Post data being sent: " + postData);

                // Write the POST data
                try (OutputStream os = conn.getOutputStream();
                     BufferedWriter writer = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"))) {
                    writer.write(postData);
                    writer.flush();
                    android.util.Log.d(TAG, "Successfully wrote POST data");
                }

                int responseCode = conn.getResponseCode();
                android.util.Log.d(TAG, "Server response code: " + responseCode);

                StringBuilder result = new StringBuilder();
                try (BufferedReader reader = new BufferedReader(new InputStreamReader(
                        (responseCode == HttpURLConnection.HTTP_OK) ? conn.getInputStream() : conn.getErrorStream()))) {
                    String line;
                    while ((line = reader.readLine()) != null) {
                        result.append(line);
                    }
                }

                String response = result.toString();
                android.util.Log.d(TAG, "Complete server response: " + response);

                runOnUiThread(() -> {
                    setLoading(false);
                    handleRegisterResponse(response, email);
                });

            } catch (Exception e) {
                android.util.Log.e(TAG, "Error during registration: " + e.getMessage());
                android.util.Log.e(TAG, "Stack trace:", e);
                runOnUiThread(() -> {
                    setLoading(false);
                    showErrorDialog("Registration Failed", 
                        "Error: " + e.getMessage() + "\n\nPlease try again.");
                });
            } finally {
                if (conn != null) {
                    conn.disconnect();
                }
            }
        }).start();
    }

    private void handleRegisterResponse(String response, String email) {
        try {
            android.util.Log.d(TAG, "Parsing response: " + response);
            JSONObject jsonObject = new JSONObject(response);
            String status = jsonObject.getString("status");
            String message = jsonObject.getString("message");

            if ("success".equals(status)) {
                String userId = jsonObject.optString("user_id", "");
                String role = rbAdmin.isChecked() ? "admin" : "client";
                String username = etUsername.getText().toString().trim();

                // Start appropriate activity based on role
                Intent intent;
                if ("admin".equals(role)) {
                    intent = new Intent(RegisterActivity.this, AdminDashboardActivity.class);
                } else {
                    intent = new Intent(RegisterActivity.this, ClientDashboardActivity.class);
                }
                
                intent.putExtra("USER_ID", userId);
                intent.putExtra("USERNAME", username);
                intent.putExtra("ROLE", role);
                startActivity(intent);
                
                // Show success message
                Toast.makeText(RegisterActivity.this, 
                    message, 
                    Toast.LENGTH_SHORT).show();
                
                finish(); // Close registration activity
            } else {
                showErrorDialog("Registration Failed", message);
            }
        } catch (Exception e) {
            android.util.Log.e(TAG, "Error parsing response: " + e.getMessage(), e);
            showErrorDialog("Error", "Could not process server response. Please try again.");
        }
    }

    private void showErrorDialog(String title, String message) {
        new AlertDialog.Builder(this)
            .setTitle(title)
            .setMessage(message)
            .setPositiveButton("OK", null)
            .show();
    }
} 