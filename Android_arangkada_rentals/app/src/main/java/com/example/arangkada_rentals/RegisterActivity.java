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
        btnRegister = findViewById(R.id.btnRegister);
        progressBar = findViewById(R.id.progressBar);
        tvLogin = findViewById(R.id.tvLogin);
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
        if (username.isEmpty() || email.isEmpty() || password.isEmpty() || confirmPassword.isEmpty()) {
            showErrorDialog("Validation Error", "All fields are required");
            return;
        }

        // Show progress and disable button
        setLoading(true);

        // Log successful validation
        android.util.Log.d(TAG, "Form validation passed, proceeding with registration");

        // Proceed with registration
        registerUser(username, email, password);
    }

    private void setLoading(boolean isLoading) {
        progressBar.setVisibility(isLoading ? View.VISIBLE : View.GONE);
        btnRegister.setEnabled(!isLoading);
        etUsername.setEnabled(!isLoading);
        etEmail.setEnabled(!isLoading);
        etPassword.setEnabled(!isLoading);
        etConfirmPassword.setEnabled(!isLoading);
    }

    private void registerUser(String username, String email, String password) {
        new Thread(() -> {
            HttpURLConnection conn = null;
            try {
                // Log network attempt
                android.util.Log.d(TAG, "Starting registration process...");
                android.util.Log.d(TAG, "Connecting to: " + REGISTER_URL);

                URL url = new URL(REGISTER_URL);
                conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
                conn.setDoOutput(true);
                conn.setDoInput(true);
                conn.setConnectTimeout(CONNECTION_TIMEOUT);
                conn.setReadTimeout(READ_TIMEOUT);

                // Create the data to send
                String data = URLEncoder.encode("username", "UTF-8") + "=" + URLEncoder.encode(username, "UTF-8") +
                        "&" + URLEncoder.encode("email", "UTF-8") + "=" + URLEncoder.encode(email, "UTF-8") +
                        "&" + URLEncoder.encode("password", "UTF-8") + "=" + URLEncoder.encode(password, "UTF-8") +
                        "&" + URLEncoder.encode("role", "UTF-8") + "=" + URLEncoder.encode("client", "UTF-8");

                // Send the request
                OutputStream os = conn.getOutputStream();
                BufferedWriter writer = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));
                writer.write(data);
                writer.flush();
                writer.close();
                os.close();

                // Get the response
                int responseCode = conn.getResponseCode();
                android.util.Log.d(TAG, "Response Code: " + responseCode);

                if (responseCode == HttpURLConnection.HTTP_OK) {
                    BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;

                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();

                    // Handle the response
                    handleRegisterResponse(response.toString(), email);
                } else {
                    runOnUiThread(() -> {
                        setLoading(false);
                        showErrorDialog("Registration Failed", "Server returned error code: " + responseCode);
                    });
                }

            } catch (Exception e) {
                android.util.Log.e(TAG, "Error during registration: " + e.getMessage());
                e.printStackTrace();
                runOnUiThread(() -> {
                    setLoading(false);
                    showErrorDialog("Registration Failed", "Error: " + e.getMessage());
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
            JSONObject jsonResponse = new JSONObject(response);
            String status = jsonResponse.optString("status");
            String message = jsonResponse.optString("message", "Unknown error occurred");

            runOnUiThread(() -> {
                setLoading(false);
                if ("success".equals(status)) {
                    Toast.makeText(RegisterActivity.this, "Registration successful!", Toast.LENGTH_SHORT).show();
                    // Start login activity
                    Intent intent = new Intent(RegisterActivity.this, MainActivity.class);
                    intent.putExtra("registered_email", email);
                    startActivity(intent);
                    finish();
                } else {
                    showErrorDialog("Registration Failed", message);
                }
            });

        } catch (Exception e) {
            android.util.Log.e(TAG, "Error parsing response: " + e.getMessage());
            e.printStackTrace();
            runOnUiThread(() -> {
                setLoading(false);
                showErrorDialog("Registration Failed", "Error processing server response");
            });
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