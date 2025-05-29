package com.example.arangkada_rentals;

import android.os.Bundle;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;

import org.json.JSONObject;
import org.json.JSONException;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

public class UserDetailsActivity extends AppCompatActivity {

    private TextView tvUsername, tvEmail, tvRole, tvStatus, tvCreatedAt, tvLastLogin;
    private ProgressBar progressBar;
    private String userId;

    private static final String GET_USER_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/get_user.php";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_user_details);

        userId = getIntent().getStringExtra("user_id");
        if (userId == null) {
            Toast.makeText(this, "Error: User ID not provided", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        initializeViews();
        setupToolbar();
        loadUserData();
    }

    private void initializeViews() {
        tvUsername = findViewById(R.id.tvUsername);
        tvEmail = findViewById(R.id.tvEmail);
        tvRole = findViewById(R.id.tvRole);
        tvStatus = findViewById(R.id.tvStatus);
        tvCreatedAt = findViewById(R.id.tvCreatedAt);
        tvLastLogin = findViewById(R.id.tvLastLogin);
        progressBar = findViewById(R.id.progressBar);
    }

    private void setupToolbar() {
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowHomeEnabled(true);
            getSupportActionBar().setTitle("User Details");
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
                        
                        SimpleDateFormat inputFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US);
                        SimpleDateFormat outputFormat = new SimpleDateFormat("MMM dd, yyyy hh:mm a", Locale.US);
                        
                        String createdAt = userData.getString("created_at");
                        Date createdDate = inputFormat.parse(createdAt);
                        String formattedCreatedAt = outputFormat.format(createdDate);

                        String lastLogin = userData.optString("last_login", null);
                        String formattedLastLogin = "Never";
                        if (lastLogin != null && !lastLogin.equals("null")) {
                            Date lastLoginDate = inputFormat.parse(lastLogin);
                            formattedLastLogin = outputFormat.format(lastLoginDate);
                        }

                        final String username = userData.getString("username");
                        final String email = userData.getString("email");
                        final String role = userData.getString("role");
                        final String status = userData.getString("status");
                        final String finalFormattedCreatedAt = formattedCreatedAt;
                        final String finalFormattedLastLogin = formattedLastLogin;

                        runOnUiThread(() -> {
                            tvUsername.setText(username);
                            tvEmail.setText(email);
                            tvRole.setText(role.substring(0, 1).toUpperCase() + role.substring(1));
                            tvStatus.setText(status.substring(0, 1).toUpperCase() + status.substring(1));
                            tvCreatedAt.setText(finalFormattedCreatedAt);
                            tvLastLogin.setText(finalFormattedLastLogin);
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

    private void setLoading(boolean isLoading) {
        runOnUiThread(() -> {
            progressBar.setVisibility(isLoading ? View.VISIBLE : View.GONE);
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