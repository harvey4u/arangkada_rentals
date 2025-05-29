package com.example.arangkada_rentals;

import android.content.Intent;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.arangkada_rentals.models.Car;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.ArrayList;
import java.util.List;

public class AvailableCarsActivity extends AppCompatActivity implements AvailableCarAdapter.OnCarClickListener {
    private RecyclerView rvCars;
    private EditText etSearch;
    private Button btnRegisterNow;
    private ProgressBar progressBar;
    private TextView tvEmpty;
    private AvailableCarAdapter carAdapter;
    private List<Car> carList;
    private List<Car> filteredCarList;

    private static final String CARS_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/cars.php";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_available_cars);

        initializeViews();
        setupToolbar();
        setupRecyclerView();
        setClickListeners();
        loadCars();
    }

    private void initializeViews() {
        rvCars = findViewById(R.id.rvCars);
        etSearch = findViewById(R.id.etSearch);
        btnRegisterNow = findViewById(R.id.btnRegisterNow);
        progressBar = findViewById(R.id.progressBar);
        tvEmpty = findViewById(R.id.tvEmpty);
        
        carList = new ArrayList<>();
        filteredCarList = new ArrayList<>();
    }

    private void setupToolbar() {
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowHomeEnabled(true);
        }
    }

    private void setupRecyclerView() {
        carAdapter = new AvailableCarAdapter(this, filteredCarList, this);
        rvCars.setLayoutManager(new LinearLayoutManager(this));
        rvCars.setAdapter(carAdapter);
    }

    private void setClickListeners() {
        etSearch.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}

            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                filterCars(s.toString());
            }

            @Override
            public void afterTextChanged(Editable s) {}
        });

        btnRegisterNow.setOnClickListener(v -> {
            Intent intent = new Intent(this, RegisterActivity.class);
            startActivity(intent);
        });
    }

    private void loadCars() {
        setLoading(true);

        new Thread(() -> {
            try {
                URL url = new URL(CARS_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");

                int responseCode = conn.getResponseCode();
                if (responseCode == HttpURLConnection.HTTP_OK) {
                    BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;

                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();

                    JSONObject jsonResponse = new JSONObject(response.toString());
                    if (jsonResponse.getString("status").equals("success")) {
                        JSONArray carsArray = jsonResponse.getJSONArray("cars");
                        carList.clear();
                        
                        for (int i = 0; i < carsArray.length(); i++) {
                            JSONObject carObject = carsArray.getJSONObject(i);
                            Car car = new Car(
                                String.valueOf(carObject.getInt("id")),
                                carObject.getString("make"),
                                carObject.getString("model"),
                                carObject.getInt("year"),
                                carObject.getString("plate_number"),
                                carObject.getDouble("price_per_day"),
                                carObject.getString("status"),
                                carObject.optString("image", "")
                            );
                            if (car.getStatus().equalsIgnoreCase("available")) {
                                carList.add(car);
                            }
                        }

                        runOnUiThread(() -> {
                            filteredCarList.clear();
                            filteredCarList.addAll(carList);
                            carAdapter.notifyDataSetChanged();
                            updateEmptyState();
                            setLoading(false);
                        });
                    } else {
                        showError("Error loading cars: " + jsonResponse.getString("message"));
                    }
                } else {
                    showError("Server returned error code: " + responseCode);
                }

            } catch (Exception e) {
                e.printStackTrace();
                showError("Error: " + e.getMessage());
            }
        }).start();
    }

    private void filterCars(String query) {
        filteredCarList.clear();
        if (query.isEmpty()) {
            filteredCarList.addAll(carList);
        } else {
            String lowerQuery = query.toLowerCase();
            for (Car car : carList) {
                if (car.getMake().toLowerCase().contains(lowerQuery) ||
                    car.getModel().toLowerCase().contains(lowerQuery) ||
                    String.valueOf(car.getYear()).contains(lowerQuery)) {
                    filteredCarList.add(car);
                }
            }
        }
        carAdapter.notifyDataSetChanged();
        updateEmptyState();
    }

    private void updateEmptyState() {
        if (filteredCarList.isEmpty()) {
            tvEmpty.setVisibility(View.VISIBLE);
            rvCars.setVisibility(View.GONE);
        } else {
            tvEmpty.setVisibility(View.GONE);
            rvCars.setVisibility(View.VISIBLE);
        }
    }

    private void setLoading(boolean isLoading) {
        runOnUiThread(() -> {
            progressBar.setVisibility(isLoading ? View.VISIBLE : View.GONE);
            rvCars.setVisibility(isLoading ? View.GONE : View.VISIBLE);
            etSearch.setEnabled(!isLoading);
            btnRegisterNow.setEnabled(!isLoading);
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

    @Override
    public void onCarClick(Car car) {
        // TODO: Implement car details view
        Toast.makeText(this, "Selected: " + car.getMake() + " " + car.getModel(), Toast.LENGTH_SHORT).show();
    }
} 