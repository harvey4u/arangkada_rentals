package com.example.arangkada_rentals;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.arangkada_rentals.adapters.RentalAdapter;
import com.example.arangkada_rentals.models.Car;
import com.example.arangkada_rentals.models.Rental;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

public class MyRentalsActivity extends AppCompatActivity implements RentalAdapter.OnRentalClickListener {
    private RecyclerView rvRentals;
    private ProgressBar progressBar;
    private TextView tvEmpty;
    private RentalAdapter rentalAdapter;
    private List<Rental> rentalList;

    private static final String API_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/rentals.php";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_my_rentals);

        // Initialize views
        initializeViews();

        // Initialize rental list and adapter
        rentalList = new ArrayList<>();
        rentalAdapter = new RentalAdapter(this, rentalList, this);
        rvRentals.setAdapter(rentalAdapter);

        // Load rentals data
        loadRentals();
    }

    private void initializeViews() {
        rvRentals = findViewById(R.id.rvRentals);
        progressBar = findViewById(R.id.progressBar);
        tvEmpty = findViewById(R.id.tvEmpty);
        
        rvRentals.setLayoutManager(new LinearLayoutManager(this));
        
        // Set up toolbar
        setSupportActionBar(findViewById(R.id.toolbar));
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("My Rentals");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }
    }

    private void loadRentals() {
        progressBar.setVisibility(View.VISIBLE);
        rvRentals.setVisibility(View.GONE);
        tvEmpty.setVisibility(View.GONE);

        new Thread(() -> {
            try {
                // Get user ID from SharedPreferences
                SharedPreferences prefs = getSharedPreferences("ArangkadaPrefs", MODE_PRIVATE);
                String userId = prefs.getString("user_id", "");

                // Build URL with user ID parameter
                URL url = new URL(API_URL + "?driver_id=" + userId);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");

                BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                StringBuilder result = new StringBuilder();
                String line;

                while ((line = reader.readLine()) != null) {
                    result.append(line);
                }
                reader.close();

                JSONObject response = new JSONObject(result.toString());
                if (response.getString("status").equals("success")) {
                    JSONArray rentalsArray = response.getJSONArray("rentals");
                    List<Rental> rentals = new ArrayList<>();
                    SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd", Locale.getDefault());

                    for (int i = 0; i < rentalsArray.length(); i++) {
                        JSONObject rentalObj = rentalsArray.getJSONObject(i);
                        JSONObject carObj = rentalObj.getJSONObject("car");

                        // Create Car object
                        Car car = new Car(
                            String.valueOf(carObj.getInt("id")),
                            carObj.getString("make"),
                            carObj.getString("model"),
                            carObj.getInt("year"),
                            carObj.getString("plate_number"),
                            carObj.getDouble("price_per_day"),
                            carObj.getString("status"),
                            carObj.getString("image")
                        );

                        // Create Rental object
                        Rental rental = new Rental(
                            String.valueOf(rentalObj.getInt("id")),
                            String.valueOf(rentalObj.getInt("car_id")),
                            rentalObj.getString("driver_id"),
                            dateFormat.parse(rentalObj.getString("start_date")),
                            dateFormat.parse(rentalObj.getString("end_date")),
                            rentalObj.getDouble("total_amount"),
                            rentalObj.getString("status"),
                            car
                        );

                        rentals.add(rental);
                    }

                    runOnUiThread(() -> {
                        progressBar.setVisibility(View.GONE);
                        if (rentals.isEmpty()) {
                            tvEmpty.setVisibility(View.VISIBLE);
                            rvRentals.setVisibility(View.GONE);
                        } else {
                            tvEmpty.setVisibility(View.GONE);
                            rvRentals.setVisibility(View.VISIBLE);
                            rentalList.clear();
                            rentalList.addAll(rentals);
                            rentalAdapter.notifyDataSetChanged();
                        }
                    });
                } else {
                    showError("Error: " + response.getString("message"));
                }
            } catch (Exception e) {
                e.printStackTrace();
                showError("Error loading rentals: " + e.getMessage());
            }
        }).start();
    }

    private void showError(String message) {
        runOnUiThread(() -> {
            progressBar.setVisibility(View.GONE);
            rvRentals.setVisibility(View.GONE);
            tvEmpty.setVisibility(View.VISIBLE);
            tvEmpty.setText(message);
            Toast.makeText(this, message, Toast.LENGTH_LONG).show();
        });
    }

    @Override
    public void onRentalClick(Rental rental) {
        Intent intent = new Intent(this, RentalDetailsActivity.class);
        intent.putExtra(RentalDetailsActivity.EXTRA_RENTAL, rental);
        startActivity(intent);
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
} 