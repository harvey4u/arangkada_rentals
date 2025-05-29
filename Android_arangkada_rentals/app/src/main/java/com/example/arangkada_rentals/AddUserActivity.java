package com.example.arangkada_rentals;

import android.os.Bundle;
import android.text.TextUtils;
import android.util.Patterns;
import android.view.View;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.RadioButton;
import android.widget.RadioGroup;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;

import com.google.android.material.textfield.TextInputLayout;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.regex.Pattern;

public class AddUserActivity extends AppCompatActivity {

    private TextInputLayout tilUsername, tilEmail, tilPassword, tilConfirmPassword;
    private RadioGroup rgRole;
    private Button btnSave;
    private ProgressBar progressBar;

    private static final String ADD_USER_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/add_user.php";
    private static final Pattern USERNAME_PATTERN = Pattern.compile("^[a-zA-Z0-9._-]{3,20}$");
    private static final Pattern PASSWORD_PATTERN = Pattern.compile("^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=])(?=\\S+$).{8,}$");

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_add_user);

        initializeViews();
        setupToolbar();
        setupSaveButton();
        setupInputValidation();
    }

    private void initializeViews() {
        tilUsername = findViewById(R.id.tilUsername);
        tilEmail = findViewById(R.id.tilEmail);
        tilPassword = findViewById(R.id.tilPassword);
        tilConfirmPassword = findViewById(R.id.tilConfirmPassword);
        rgRole = findViewById(R.id.rgRole);
        btnSave = findViewById(R.id.btnSave);
        progressBar = findViewById(R.id.progressBar);
    }

    private void setupToolbar() {
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowHomeEnabled(true);
        }
    }

    private void setupInputValidation() {
        // Real-time username validation
        tilUsername.getEditText().setOnFocusChangeListener((v, hasFocus) -> {
            if (!hasFocus) {
                String username = tilUsername.getEditText().getText().toString().trim();
                validateUsername(username);
            }
        });

        // Real-time password validation
        tilPassword.getEditText().setOnFocusChangeListener((v, hasFocus) -> {
            if (!hasFocus) {
                String password = tilPassword.getEditText().getText().toString();
                validatePassword(password);
            }
        });

        // Real-time confirm password validation
        tilConfirmPassword.getEditText().setOnFocusChangeListener((v, hasFocus) -> {
            if (!hasFocus) {
                String password = tilPassword.getEditText().getText().toString();
                String confirmPassword = tilConfirmPassword.getEditText().getText().toString();
                validateConfirmPassword(password, confirmPassword);
            }
        });
    }

    private void setupSaveButton() {
        btnSave.setOnClickListener(v -> {
            if (validateForm()) {
                addUser();
            }
        });
    }

    private boolean validateUsername(String username) {
        if (TextUtils.isEmpty(username)) {
            tilUsername.setError("Username is required");
            return false;
        } else if (!USERNAME_PATTERN.matcher(username).matches()) {
            tilUsername.setError("Username must be 3-20 characters and can only contain letters, numbers, dots, underscores, and hyphens");
            return false;
        } else {
            tilUsername.setError(null);
            return true;
        }
    }

    private boolean validatePassword(String password) {
        if (TextUtils.isEmpty(password)) {
            tilPassword.setError("Password is required");
            return false;
        } else if (!PASSWORD_PATTERN.matcher(password).matches()) {
            tilPassword.setError("Password must contain at least 8 characters, including uppercase, lowercase, number, and special character");
            return false;
        } else {
            tilPassword.setError(null);
            return true;
        }
    }

    private boolean validateConfirmPassword(String password, String confirmPassword) {
        if (TextUtils.isEmpty(confirmPassword)) {
            tilConfirmPassword.setError("Please confirm password");
            return false;
        } else if (!password.equals(confirmPassword)) {
            tilConfirmPassword.setError("Passwords do not match");
            return false;
        } else {
            tilConfirmPassword.setError(null);
            return true;
        }
    }

    private boolean validateForm() {
        String username = tilUsername.getEditText().getText().toString().trim();
        String email = tilEmail.getEditText().getText().toString().trim();
        String password = tilPassword.getEditText().getText().toString();
        String confirmPassword = tilConfirmPassword.getEditText().getText().toString();

        boolean isValid = true;

        // Validate username
        if (!validateUsername(username)) {
            isValid = false;
        }

        // Validate email
        if (TextUtils.isEmpty(email)) {
            tilEmail.setError("Email is required");
            isValid = false;
        } else if (!Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            tilEmail.setError("Invalid email address");
            isValid = false;
        } else {
            tilEmail.setError(null);
        }

        // Validate password
        if (!validatePassword(password)) {
            isValid = false;
        }

        // Validate confirm password
        if (!validateConfirmPassword(password, confirmPassword)) {
            isValid = false;
        }

        // Validate role selection
        if (rgRole.getCheckedRadioButtonId() == -1) {
            Toast.makeText(this, "Please select a role", Toast.LENGTH_SHORT).show();
            isValid = false;
        }

        return isValid;
    }

    private void addUser() {
        setLoading(true);

        new Thread(() -> {
            try {
                // Prepare request data
                String username = tilUsername.getEditText().getText().toString().trim();
                String email = tilEmail.getEditText().getText().toString().trim();
                String password = tilPassword.getEditText().getText().toString();
                RadioButton selectedRole = findViewById(rgRole.getCheckedRadioButtonId());
                String role = selectedRole.getText().toString().toLowerCase();

                JSONObject requestData = new JSONObject();
                requestData.put("username", username);
                requestData.put("email", email);
                requestData.put("password", password);
                requestData.put("role", role);

                // Make API request
                URL url = new URL(ADD_USER_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);

                try (OutputStream os = conn.getOutputStream()) {
                    byte[] input = requestData.toString().getBytes(StandardCharsets.UTF_8);
                    os.write(input, 0, input.length);
                }

                int responseCode = conn.getResponseCode();
                BufferedReader reader;
                if (responseCode == HttpURLConnection.HTTP_OK) {
                    reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                } else {
                    reader = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
                }

                StringBuilder response = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    response.append(line);
                }
                reader.close();

                JSONObject jsonResponse = new JSONObject(response.toString());
                final String message = jsonResponse.getString("message");

                runOnUiThread(() -> {
                    if (responseCode == HttpURLConnection.HTTP_OK) {
                        Toast.makeText(this, message, Toast.LENGTH_SHORT).show();
                        setResult(RESULT_OK);
                        finish();
                    } else {
                        showError(message);
                    }
                });

            } catch (Exception e) {
                e.printStackTrace();
                showError("Error: " + e.getMessage());
            } finally {
                setLoading(false);
            }
        }).start();
    }

    private void setLoading(boolean isLoading) {
        runOnUiThread(() -> {
            progressBar.setVisibility(isLoading ? View.VISIBLE : View.GONE);
            btnSave.setEnabled(!isLoading);
            tilUsername.setEnabled(!isLoading);
            tilEmail.setEnabled(!isLoading);
            tilPassword.setEnabled(!isLoading);
            tilConfirmPassword.setEnabled(!isLoading);
            rgRole.setEnabled(!isLoading);
        });
    }

    private void showError(String message) {
        runOnUiThread(() -> {
            Toast.makeText(this, message, Toast.LENGTH_LONG).show();
            setLoading(false);
        });
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
} 