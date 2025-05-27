package com.example.arangkada_rentals;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.ProgressBar;
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

public class VerificationActivity extends AppCompatActivity {

    private Button btnResendEmail, btnProceedToLogin;
    private ProgressBar progressBar;
    private String email;
    private static final String RESEND_VERIFICATION_URL = "http://10.0.2.2/ARANGKADA/arangkada_rentals/api/resend_verification.php";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_verification);

        // Get email from intent
        email = getIntent().getStringExtra("email");
        if (email == null) {
            Toast.makeText(this, "Error: Email not provided", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        // Initialize views
        btnResendEmail = findViewById(R.id.btnResendEmail);
        btnProceedToLogin = findViewById(R.id.btnProceedToLogin);
        progressBar = findViewById(R.id.progressBar);

        // Set click listeners
        btnResendEmail.setOnClickListener(v -> resendVerificationEmail());
        btnProceedToLogin.setOnClickListener(v -> {
            startActivity(new Intent(VerificationActivity.this, MainActivity.class));
            finish();
        });
    }

    private void resendVerificationEmail() {
        progressBar.setVisibility(View.VISIBLE);
        btnResendEmail.setEnabled(false);

        new Thread(() -> {
            try {
                URL url = new URL(RESEND_VERIFICATION_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setDoInput(true);
                conn.setDoOutput(true);

                // Send email in POST request
                String postData = "email=" + URLEncoder.encode(email, "UTF-8");
                
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
                    btnResendEmail.setEnabled(true);
                    handleResendResponse(response);
                });

            } catch (Exception e) {
                e.printStackTrace();
                runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    btnResendEmail.setEnabled(true);
                    Toast.makeText(VerificationActivity.this, 
                        "Error: " + e.getMessage(), Toast.LENGTH_LONG).show();
                });
            }
        }).start();
    }

    private void handleResendResponse(String response) {
        try {
            JSONObject jsonObject = new JSONObject(response);
            String status = jsonObject.getString("status");
            String message = jsonObject.getString("message");

            if ("success".equals(status)) {
                Toast.makeText(this, "✅ " + message, Toast.LENGTH_LONG).show();
            } else {
                Toast.makeText(this, "❌ " + message, Toast.LENGTH_LONG).show();
            }
        } catch (Exception e) {
            Toast.makeText(this, "Error parsing response", Toast.LENGTH_LONG).show();
        }
    }
} 