package com.example.arangkada_rentals;

import android.content.Intent;
import android.content.SharedPreferences;
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

import androidx.appcompat.app.AlertDialog;
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

public class LoginActivity extends AppCompatActivity {

    private EditText etEmail, etPassword;
    private Button btnLogin;
    private ProgressBar progressBar;
    private TextView tvRegister;

    private static final String TAG = "LoginActivity";
    private static final String LOGIN_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/login.php";
    private static final String PREF_NAME = "ArangkadaPrefs";
    private static final int CONNECTION_TIMEOUT = 30000;
    private static final int READ_TIMEOUT = 30000;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        // Initialize views
        initializeViews();
        
        // Set click listeners
        setClickListeners();

        // Check if email was passed from registration
        String registeredEmail = getIntent().getStringExtra("registered_email");
        if (registeredEmail != null && !registeredEmail.isEmpty()) {
            etEmail.setText(registeredEmail);
            etPassword.requestFocus();
        }
    }

    private void initializeViews() {
        etEmail = findViewById(R.id.etEmail);
        etPassword = findViewById(R.id.etPassword);
        btnLogin = findViewById(R.id.btnLogin);
        progressBar = findViewById(R.id.progressBar);
        tvRegister = findViewById(R.id.tvRegister);
    }

    private void setClickListeners() {
        btnLogin.setOnClickListener(v -> {
            if (!isNetworkAvailable()) {
                showErrorDialog("No Internet Connection", 
                    "Please check your internet connection and try again.");
                return;
            }
            validateForm();
        });

        tvRegister.setOnClickListener(v -> {
            Intent intent = new Intent(LoginActivity.this, RegisterActivity.class);
            startActivity(intent);
            finish();
        });
    }

    private boolean isNetworkAvailable() {
        ConnectivityManager connectivityManager = (ConnectivityManager) getSystemService(CONNECTIVITY_SERVICE);
        NetworkInfo activeNetworkInfo = connectivityManager.getActiveNetworkInfo();
        return activeNetworkInfo != null && activeNetworkInfo.isConnected();
    }

    private void validateForm() {
        String email = etEmail.getText().toString().trim();
        String password = etPassword.getText().toString().trim();

        // Clear previous errors
        etEmail.setError(null);
        etPassword.setError(null);

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

        // Show progress and disable inputs
        setLoading(true);

        // Proceed with login
        loginUser(email, password);
    }

    private void loginUser(String email, String password) {
        new Thread(() -> {
            HttpURLConnection conn = null;
            try {
                URL url = new URL(LOGIN_URL);
                conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
                conn.setDoOutput(true);
                conn.setDoInput(true);
                conn.setConnectTimeout(CONNECTION_TIMEOUT);
                conn.setReadTimeout(READ_TIMEOUT);

                // Create the POST data
                String postData = URLEncoder.encode("email", "UTF-8") + "=" + URLEncoder.encode(email, "UTF-8") +
                        "&" + URLEncoder.encode("password", "UTF-8") + "=" + URLEncoder.encode(password, "UTF-8");

                // Send the request
                OutputStream os = conn.getOutputStream();
                BufferedWriter writer = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));
                writer.write(postData);
                writer.flush();
                writer.close();
                os.close();

                int responseCode = conn.getResponseCode();
                if (responseCode == HttpURLConnection.HTTP_OK) {
                    BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;

                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();

                    handleLoginResponse(response.toString());
                } else {
                    showError("Server returned error code: " + responseCode);
                }

            } catch (Exception e) {
                e.printStackTrace();
                showError("Error: " + e.getMessage());
            } finally {
                if (conn != null) {
                    conn.disconnect();
                }
            }
        }).start();
    }

    private void handleLoginResponse(String response) {
        try {
            JSONObject jsonResponse = new JSONObject(response);
            String status = jsonResponse.optString("status");
            String message = jsonResponse.optString("message", "Unknown error occurred");

            if ("success".equals(status)) {
                JSONObject userData = jsonResponse.getJSONObject("user");
                String userId = userData.getString("id");
                String username = userData.getString("username");
                String role = userData.getString("role");

                // Save user data
                saveUserData(userId, username, role);

                // Redirect based on role
                redirectBasedOnRole(role);
            } else {
                showError(message);
            }

        } catch (Exception e) {
            e.printStackTrace();
            showError("Error processing server response");
        }
    }

    private void saveUserData(String userId, String username, String role) {
        SharedPreferences prefs = getSharedPreferences(PREF_NAME, MODE_PRIVATE);
        SharedPreferences.Editor editor = prefs.edit();
        editor.putString("user_id", userId);
        editor.putString("username", username);
        editor.putString("role", role);
        editor.apply();
    }

    private void redirectBasedOnRole(String role) {
        Intent intent;
        switch (role.toLowerCase()) {
            case "admin":
                intent = new Intent(LoginActivity.this, AdminDashboardActivity.class);
                break;
            case "staff":
                intent = new Intent(LoginActivity.this, StaffDashboardActivity.class);
                break;
            case "driver":
                intent = new Intent(LoginActivity.this, DriverDashboardActivity.class);
                break;
            default: // client
                intent = new Intent(LoginActivity.this, ClientDashboardActivity.class);
                break;
        }
        
        runOnUiThread(() -> {
            startActivity(intent);
            finish();
        });
    }

    private void setLoading(boolean isLoading) {
        runOnUiThread(() -> {
            progressBar.setVisibility(isLoading ? View.VISIBLE : View.GONE);
            etEmail.setEnabled(!isLoading);
            etPassword.setEnabled(!isLoading);
            btnLogin.setEnabled(!isLoading);
            tvRegister.setEnabled(!isLoading);
        });
    }

    private void showError(String message) {
        runOnUiThread(() -> {
            Toast.makeText(this, message, Toast.LENGTH_LONG).show();
            setLoading(false);
        });
    }

    private void showErrorDialog(String title, String message) {
        new AlertDialog.Builder(this)
            .setTitle(title)
            .setMessage(message)
            .setPositiveButton("OK", null)
            .show();
    }
} 