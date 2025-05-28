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
    private static final String REGISTER_URL = "http://10.0.2.2/ARANGKADA/arangkada_rentals/api/register.php";
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
        // Get form data
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

        // Log form data (remove in production)
        android.util.Log.d(TAG, "Validating form data:");
        android.util.Log.d(TAG, "Username: " + username);
        android.util.Log.d(TAG, "Email: " + email);
        android.util.Log.d(TAG, "Role: " + role);

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
        setLoading(true);

        // Log that validation passed
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
                URL url = new URL(REGISTER_URL);
                conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setDoInput(true);
                conn.setDoOutput(true);
                conn.setConnectTimeout(CONNECTION_TIMEOUT);
                conn.setReadTimeout(READ_TIMEOUT);
                conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
                conn.setRequestProperty("Accept", "application/json");

                // Log connection attempt
                android.util.Log.d(TAG, "Attempting to connect to: " + REGISTER_URL);

                // Create the POST data string
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

                // Log post data (remove in production)
                android.util.Log.d(TAG, "Post data: " + postData);

                // Write the POST data to the connection
                try (OutputStream os = conn.getOutputStream();
                     BufferedWriter writer = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"))) {
                    writer.write(postData);
                    writer.flush();
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
                android.util.Log.d(TAG, "Server response: " + response);

                runOnUiThread(() -> {
                    setLoading(false);
                    handleRegisterResponse(response, email);
                });

            } catch (java.net.SocketTimeoutException e) {
                android.util.Log.e(TAG, "Connection timeout: " + e.getMessage());
                runOnUiThread(() -> {
                    setLoading(false);
                    showErrorDialog("Connection Timeout", 
                        "The server is taking too long to respond. Please try again later.");
                });
            } catch (java.net.UnknownHostException e) {
                android.util.Log.e(TAG, "Unknown host: " + e.getMessage());
                runOnUiThread(() -> {
                    setLoading(false);
                    showErrorDialog("Server Not Found", 
                        "Could not find the server. Please check your internet connection and try again.");
                });
            } catch (Exception e) {
                android.util.Log.e(TAG, "Error during registration: " + e.getMessage(), e);
                runOnUiThread(() -> {
                    setLoading(false);
                    showErrorDialog("Registration Failed", 
                        "Error: " + e.getMessage() + "\nPlease try again later.");
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
                showVerificationDialog(email);
            } else {
                showErrorDialog("Registration Failed", message);
            }
        } catch (Exception e) {
            android.util.Log.e(TAG, "Error parsing response: " + e.getMessage(), e);
            showErrorDialog("Error", "Could not process server response. Please try again.");
        }
    }

    private void showVerificationDialog(String email) {
        new AlertDialog.Builder(this)
            .setTitle("✉️ Check Your Email!")
            .setMessage("We've sent a verification email to " + email + ".\n\n" +
                       "Please check your email and click the verification link to complete your registration.\n\n" +
                       "Important Notes:\n" +
                       "• The verification link will expire in 24 hours\n" +
                       "• Check your spam folder if you don't see the email\n" +
                       "• Make sure to verify your email before logging in")
            .setPositiveButton("Open Email App", (dialog, which) -> {
                // Try to open email app
                Intent intent = new Intent(Intent.ACTION_MAIN);
                intent.addCategory(Intent.CATEGORY_APP_EMAIL);
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                try {
                    startActivity(intent);
                } catch (android.content.ActivityNotFoundException ex) {
                    Toast.makeText(this, "No email app found.", Toast.LENGTH_SHORT).show();
                } finally {
                    // Return to login screen
                    startActivity(new Intent(RegisterActivity.this, MainActivity.class));
                    finish();
                }
            })
            .setNegativeButton("Later", (dialog, which) -> {
                startActivity(new Intent(RegisterActivity.this, MainActivity.class));
                finish();
            })
            .setCancelable(false)
            .show();
    }

    private void showErrorDialog(String title, String message) {
        new AlertDialog.Builder(this)
            .setTitle(title)
            .setMessage(message)
            .setPositiveButton("OK", null)
            .show();
    }
} 