package com.example.arangkada_rentals;

import android.os.Bundle;
import android.text.TextUtils;
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
import org.json.JSONException;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

public class EditUserActivity extends AppCompatActivity {

    private TextInputLayout tilUsername, tilEmail;
    private RadioGroup rgRole;
    private Button btnSave;
    private ProgressBar progressBar;
    private String userId;

    private static final String UPDATE_USER_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/update_user.php";
    private static final String GET_USER_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/get_user.php";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_edit_user);

        userId = getIntent().getStringExtra("user_id");
        if (userId == null) {
            Toast.makeText(this, "Error: User ID not provided", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        initializeViews();
        setupToolbar();
        loadUserData();
        setupSaveButton();
    }

    private void initializeViews() {
        tilUsername = findViewById(R.id.tilUsername);
        tilEmail = findViewById(R.id.tilEmail);
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

    private void loadUserData() {
        setLoading(true);

        new Thread(() -> {
            try {
                URL url = new URL(GET_USER_URL + "?id=" + userId);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");

                int responseCode = conn.getResponseCode();
                if (responseCode != HttpURLConnection.HTTP_OK) {
                    showError("Server returned error code: " + responseCode);
                    return;
                }

                BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                StringBuilder response = new StringBuilder();
                String line;

                while ((line = reader.readLine()) != null) {
                    response.append(line);
                }
                reader.close();

                try {
                    JSONObject jsonResponse = new JSONObject(response.toString());
                    if (jsonResponse.getString("status").equals("success")) {
                        JSONObject userData = jsonResponse.getJSONObject("user");
                        
                        runOnUiThread(() -> {
                            try {
                                tilUsername.getEditText().setText(userData.getString("username"));
                                tilEmail.getEditText().setText(userData.getString("email"));
                                
                                String role = userData.getString("role");
                                int radioButtonId = -1;
                                switch (role) {
                                    case "staff":
                                        radioButtonId = R.id.rbStaff;
                                        break;
                                    case "driver":
                                        radioButtonId = R.id.rbDriver;
                                        break;
                                    case "client":
                                        radioButtonId = R.id.rbClient;
                                        break;
                                }
                                if (radioButtonId != -1) {
                                    rgRole.check(radioButtonId);
                                }
                            } catch (JSONException e) {
                                showError("Error parsing user data: " + e.getMessage());
                            }
                        });
                    } else {
                        showError(jsonResponse.optString("message", "Error loading user data"));
                    }
                } catch (JSONException e) {
                    showError("Error parsing server response: " + e.getMessage());
                }
            } catch (Exception e) {
                e.printStackTrace();
                showError("Network error: " + e.getMessage());
            } finally {
                setLoading(false);
            }
        }).start();
    }

    private void setupSaveButton() {
        btnSave.setOnClickListener(v -> {
            if (validateForm()) {
                updateUser();
            }
        });
    }

    private boolean validateForm() {
        boolean isValid = true;

        String username = tilUsername.getEditText().getText().toString().trim();
        if (TextUtils.isEmpty(username)) {
            tilUsername.setError("Username is required");
            isValid = false;
        } else {
            tilUsername.setError(null);
        }

        String email = tilEmail.getEditText().getText().toString().trim();
        if (TextUtils.isEmpty(email)) {
            tilEmail.setError("Email is required");
            isValid = false;
        } else {
            tilEmail.setError(null);
        }

        if (rgRole.getCheckedRadioButtonId() == -1) {
            Toast.makeText(this, "Please select a role", Toast.LENGTH_SHORT).show();
            isValid = false;
        }

        return isValid;
    }

    private void updateUser() {
        setLoading(true);

        new Thread(() -> {
            try {
                String username = tilUsername.getEditText().getText().toString().trim();
                String email = tilEmail.getEditText().getText().toString().trim();
                RadioButton selectedRole = findViewById(rgRole.getCheckedRadioButtonId());
                String role = selectedRole.getText().toString().toLowerCase();

                JSONObject requestData = new JSONObject();
                try {
                    requestData.put("id", userId);
                    requestData.put("username", username);
                    requestData.put("email", email);
                    requestData.put("role", role);
                } catch (JSONException e) {
                    showError("Error creating request data: " + e.getMessage());
                    return;
                }

                URL url = new URL(UPDATE_USER_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);

                try (OutputStream os = conn.getOutputStream()) {
                    byte[] input = requestData.toString().getBytes(StandardCharsets.UTF_8);
                    os.write(input, 0, input.length);
                }

                int responseCode = conn.getResponseCode();
                if (responseCode != HttpURLConnection.HTTP_OK) {
                    showError("Server returned error code: " + responseCode);
                    return;
                }

                BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                StringBuilder response = new StringBuilder();
                String line;

                while ((line = reader.readLine()) != null) {
                    response.append(line);
                }
                reader.close();

                try {
                    JSONObject jsonResponse = new JSONObject(response.toString());
                    final String message = jsonResponse.optString("message", "Operation completed");
                    final String status = jsonResponse.optString("status");

                    runOnUiThread(() -> {
                        Toast.makeText(EditUserActivity.this, message, Toast.LENGTH_SHORT).show();
                        if ("success".equals(status)) {
                            setResult(RESULT_OK);
                            finish();
                        }
                    });
                } catch (JSONException e) {
                    showError("Error parsing server response: " + e.getMessage());
                }

            } catch (Exception e) {
                e.printStackTrace();
                showError("Network error: " + e.getMessage());
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