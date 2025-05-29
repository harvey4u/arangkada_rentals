package com.example.arangkada_rentals;

import android.app.Dialog;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.arangkada_rentals.models.Car;
import com.example.arangkada_rentals.adapters.CarAdapter;
import com.google.android.material.floatingactionbutton.FloatingActionButton;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONArray;
import org.json.JSONObject;
import org.json.JSONException;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.ArrayList;
import java.util.List;

public class CarsFragment extends Fragment implements CarAdapter.OnCarClickListener {

    private RecyclerView rvCars;
    private FloatingActionButton fabAddCar;
    private CarAdapter carAdapter;
    private List<Car> carList;

    private static final String CARS_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/cars.php";

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_cars, container, false);

        rvCars = view.findViewById(R.id.rvCars);
        fabAddCar = view.findViewById(R.id.fabAddCar);

        // Initialize car list and adapter
        carList = new ArrayList<>();
        carAdapter = new CarAdapter(requireContext(), carList, this);
        rvCars.setLayoutManager(new LinearLayoutManager(getContext()));
        rvCars.setAdapter(carAdapter);

        // Set up FAB click listener
        fabAddCar.setOnClickListener(v -> showCarDialog(null));

        // Load cars data
        loadCars();

        return view;
    }

    public void showCarDialog(Car car) {
        Dialog dialog = new Dialog(requireContext());
        dialog.setContentView(R.layout.dialog_car_form);

        TextView tvTitle = dialog.findViewById(R.id.tvTitle);
        TextInputEditText etMake = dialog.findViewById(R.id.etMake);
        TextInputEditText etModel = dialog.findViewById(R.id.etModel);
        TextInputEditText etYear = dialog.findViewById(R.id.etYear);
        TextInputEditText etPlateNumber = dialog.findViewById(R.id.etPlateNumber);
        TextInputEditText etPrice = dialog.findViewById(R.id.etPrice);
        Spinner spinnerCategory = dialog.findViewById(R.id.spinnerCategory);
        Button btnCancel = dialog.findViewById(R.id.btnCancel);
        Button btnSave = dialog.findViewById(R.id.btnSave);

        // Set up category spinner
        ArrayAdapter<String> categoryAdapter = new ArrayAdapter<>(
            requireContext(),
            android.R.layout.simple_spinner_item,
            new String[]{"Economy", "Luxury", "SUV", "Sports"}
        );
        categoryAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinnerCategory.setAdapter(categoryAdapter);

        // Set title and populate fields if editing
        if (car != null) {
            tvTitle.setText("Edit Car");
            etMake.setText(car.getMake());
            etModel.setText(car.getModel());
            etYear.setText(String.valueOf(car.getYear()));
            etPlateNumber.setText(car.getPlateNumber());
            etPrice.setText(String.valueOf(car.getPrice()));
            // TODO: Set category spinner selection
        }

        btnCancel.setOnClickListener(v -> dialog.dismiss());
        btnSave.setOnClickListener(v -> {
            // TODO: Implement save functionality
            dialog.dismiss();
        });

        dialog.show();
    }

    private void loadCars() {
        new Thread(() -> {
            try {
                URL url = new URL(CARS_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");

                BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                StringBuilder result = new StringBuilder();
                String line;

                while ((line = reader.readLine()) != null) {
                    result.append(line);
                }
                reader.close();

                try {
                    JSONObject response = new JSONObject(result.toString());
                    String status = response.optString("status", "");
                    if ("success".equals(status)) {
                        JSONArray carsArray = response.getJSONArray("cars");
                        List<Car> cars = new ArrayList<>();

                        for (int i = 0; i < carsArray.length(); i++) {
                            try {
                                JSONObject carObj = carsArray.getJSONObject(i);
                                Car car = new Car(
                                    String.valueOf(carObj.optInt("id", 0)),
                                    carObj.optString("make", ""),
                                    carObj.optString("model", ""),
                                    carObj.optInt("year", 0),
                                    carObj.optString("plate_number", ""),
                                    carObj.optDouble("price_per_day", 0.0),
                                    carObj.optString("status", "unavailable"),
                                    carObj.optString("image", "")
                                );
                                cars.add(car);
                            } catch (JSONException e) {
                                e.printStackTrace();
                                continue;
                            }
                        }

                        if (getActivity() != null) {
                            getActivity().runOnUiThread(() -> {
                                carList.clear();
                                carList.addAll(cars);
                                carAdapter.notifyDataSetChanged();
                            });
                        }
                    } else {
                        showError("Error loading cars: " + response.optString("message", "Unknown error"));
                    }
                } catch (JSONException e) {
                    showError("Error parsing response: " + e.getMessage());
                }
            } catch (Exception e) {
                e.printStackTrace();
                showError("Network error: " + e.getMessage());
            }
        }).start();
    }

    public void deleteCar(String carId) {
        // TODO: Implement delete functionality
        Toast.makeText(requireContext(), "Deleting car with ID: " + carId, Toast.LENGTH_SHORT).show();
    }

    private void showError(String message) {
        if (getActivity() != null) {
            getActivity().runOnUiThread(() ->
                Toast.makeText(getContext(), message, Toast.LENGTH_LONG).show()
            );
        }
    }

    @Override
    public void onCarClick(Car car) {
        showCarDialog(car);
    }
} 