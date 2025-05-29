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

public class DriversFragment extends Fragment {

    private RecyclerView rvDrivers;
    private FloatingActionButton fabAddDriver;
    private DriverAdapter driverAdapter;
    private List<Driver> driverList;

    private static final String DRIVERS_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/drivers.php";

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_drivers, container, false);

        rvDrivers = view.findViewById(R.id.rvDrivers);
        fabAddDriver = view.findViewById(R.id.fabAddDriver);

        // Initialize driver list and adapter
        driverList = new ArrayList<>();
        driverAdapter = new DriverAdapter(driverList);
        driverAdapter.setFragment(this);
        rvDrivers.setLayoutManager(new LinearLayoutManager(getContext()));
        rvDrivers.setAdapter(driverAdapter);

        // Set up FAB click listener
        fabAddDriver.setOnClickListener(v -> showDriverDialog(null));

        // Load drivers data
        loadDrivers();

        return view;
    }

    public void showDriverDialog(Driver driver) {
        Dialog dialog = new Dialog(requireContext());
        dialog.setContentView(R.layout.dialog_driver_form);

        TextView tvTitle = dialog.findViewById(R.id.tvTitle);
        TextInputEditText etUsername = dialog.findViewById(R.id.etUsername);
        TextInputEditText etEmail = dialog.findViewById(R.id.etEmail);
        TextInputEditText etLicenseNumber = dialog.findViewById(R.id.etLicenseNumber);
        TextInputEditText etLicenseExpiry = dialog.findViewById(R.id.etLicenseExpiry);
        TextInputEditText etContactNumber = dialog.findViewById(R.id.etContactNumber);
        TextInputEditText etAddress = dialog.findViewById(R.id.etAddress);
        Spinner spinnerStatus = dialog.findViewById(R.id.spinnerStatus);
        Button btnCancel = dialog.findViewById(R.id.btnCancel);
        Button btnSave = dialog.findViewById(R.id.btnSave);

        // Set up status spinner
        ArrayAdapter<String> statusAdapter = new ArrayAdapter<>(
            requireContext(),
            android.R.layout.simple_spinner_item,
            new String[]{"Active", "Inactive", "Suspended"}
        );
        statusAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinnerStatus.setAdapter(statusAdapter);

        // Set title and populate fields if editing
        if (driver != null) {
            tvTitle.setText("Edit Driver");
            etUsername.setText(driver.username);
            etEmail.setText(driver.email);
            etLicenseNumber.setText(driver.licenseNumber);
            etLicenseExpiry.setText(driver.licenseExpiry);
            etContactNumber.setText(driver.contactNumber);
            etAddress.setText(driver.address);
            // Set status spinner selection
            int statusPosition = statusAdapter.getPosition(driver.status);
            if (statusPosition != -1) {
                spinnerStatus.setSelection(statusPosition);
            }
        }

        btnCancel.setOnClickListener(v -> dialog.dismiss());

        btnSave.setOnClickListener(v -> {
            // Validate input
            String username = etUsername.getText().toString().trim();
            String email = etEmail.getText().toString().trim();
            String licenseNumber = etLicenseNumber.getText().toString().trim();
            String licenseExpiry = etLicenseExpiry.getText().toString().trim();
            String contactNumber = etContactNumber.getText().toString().trim();
            String address = etAddress.getText().toString().trim();
            String status = spinnerStatus.getSelectedItem().toString();

            if (username.isEmpty() || email.isEmpty() || licenseNumber.isEmpty() || 
                licenseExpiry.isEmpty() || contactNumber.isEmpty() || address.isEmpty()) {
                Toast.makeText(getContext(), "Please fill in all fields", Toast.LENGTH_SHORT).show();
                return;
            }

            // Save driver
            if (driver == null) {
                createDriver(username, email, licenseNumber, licenseExpiry, contactNumber, address, status, dialog);
            } else {
                updateDriver(driver.id, username, email, licenseNumber, licenseExpiry, contactNumber, address, status, dialog);
            }
        });

        dialog.show();
    }

    private void createDriver(String username, String email, String licenseNumber, String licenseExpiry,
                            String contactNumber, String address, String status, Dialog dialog) {
        new Thread(() -> {
            try {
                URL url = new URL(DRIVERS_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);

                JSONObject driverData = new JSONObject();
                try {
                    driverData.put("username", username);
                    driverData.put("email", email);
                    driverData.put("license_number", licenseNumber);
                    driverData.put("license_expiry", licenseExpiry);
                    driverData.put("contact_number", contactNumber);
                    driverData.put("address", address);
                    driverData.put("status", status.toLowerCase());
                } catch (JSONException e) {
                    e.printStackTrace();
                    if (getActivity() != null) {
                        getActivity().runOnUiThread(() -> 
                            Toast.makeText(getContext(), "Error preparing data", Toast.LENGTH_LONG).show()
                        );
                    }
                    return;
                }

                OutputStream os = conn.getOutputStream();
                BufferedWriter writer = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));
                writer.write(driverData.toString());
                writer.flush();
                writer.close();
                os.close();

                BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                StringBuilder result = new StringBuilder();
                String line;

                while ((line = reader.readLine()) != null) {
                    result.append(line);
                }
                reader.close();

                try {
                    JSONObject response = new JSONObject(result.toString());
                    String status_response = response.optString("status", "");
                    if ("success".equals(status_response)) {
                        if (getActivity() != null) {
                            getActivity().runOnUiThread(() -> {
                                dialog.dismiss();
                                Toast.makeText(getContext(), "Driver created successfully", Toast.LENGTH_SHORT).show();
                                loadDrivers();
                            });
                        }
                    } else {
                        String message = response.optString("message", "Unknown error occurred");
                        if (getActivity() != null) {
                            getActivity().runOnUiThread(() -> 
                                Toast.makeText(getContext(), "Error: " + message, Toast.LENGTH_LONG).show()
                            );
                        }
                    }
                } catch (JSONException e) {
                    e.printStackTrace();
                    if (getActivity() != null) {
                        getActivity().runOnUiThread(() -> 
                            Toast.makeText(getContext(), "Error parsing server response", Toast.LENGTH_LONG).show()
                        );
                    }
                }

            } catch (Exception e) {
                e.printStackTrace();
                if (getActivity() != null) {
                    getActivity().runOnUiThread(() -> 
                        Toast.makeText(getContext(), "Network error: " + e.getMessage(), Toast.LENGTH_LONG).show()
                    );
                }
            }
        }).start();
    }

    private void updateDriver(int id, String username, String email, String licenseNumber, String licenseExpiry,
                            String contactNumber, String address, String status, Dialog dialog) {
        new Thread(() -> {
            try {
                URL url = new URL(DRIVERS_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("PUT");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);

                JSONObject driverData = new JSONObject();
                try {
                    driverData.put("id", id);
                    driverData.put("username", username);
                    driverData.put("email", email);
                    driverData.put("license_number", licenseNumber);
                    driverData.put("license_expiry", licenseExpiry);
                    driverData.put("contact_number", contactNumber);
                    driverData.put("address", address);
                    driverData.put("status", status.toLowerCase());
                } catch (JSONException e) {
                    e.printStackTrace();
                    if (getActivity() != null) {
                        getActivity().runOnUiThread(() -> 
                            Toast.makeText(getContext(), "Error preparing data", Toast.LENGTH_LONG).show()
                        );
                    }
                    return;
                }

                OutputStream os = conn.getOutputStream();
                BufferedWriter writer = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));
                writer.write(driverData.toString());
                writer.flush();
                writer.close();
                os.close();

                BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                StringBuilder result = new StringBuilder();
                String line;

                while ((line = reader.readLine()) != null) {
                    result.append(line);
                }
                reader.close();

                try {
                    JSONObject response = new JSONObject(result.toString());
                    String status_response = response.optString("status", "");
                    if ("success".equals(status_response)) {
                        if (getActivity() != null) {
                            getActivity().runOnUiThread(() -> {
                                dialog.dismiss();
                                Toast.makeText(getContext(), "Driver updated successfully", Toast.LENGTH_SHORT).show();
                                loadDrivers();
                            });
                        }
                    } else {
                        String message = response.optString("message", "Unknown error occurred");
                        if (getActivity() != null) {
                            getActivity().runOnUiThread(() -> 
                                Toast.makeText(getContext(), "Error: " + message, Toast.LENGTH_LONG).show()
                            );
                        }
                    }
                } catch (JSONException e) {
                    e.printStackTrace();
                    if (getActivity() != null) {
                        getActivity().runOnUiThread(() -> 
                            Toast.makeText(getContext(), "Error parsing server response", Toast.LENGTH_LONG).show()
                        );
                    }
                }

            } catch (Exception e) {
                e.printStackTrace();
                if (getActivity() != null) {
                    getActivity().runOnUiThread(() -> 
                        Toast.makeText(getContext(), "Network error: " + e.getMessage(), Toast.LENGTH_LONG).show()
                    );
                }
            }
        }).start();
    }

    public void deleteDriver(int id) {
        new Thread(() -> {
            try {
                URL url = new URL(DRIVERS_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("DELETE");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);

                JSONObject driverData = new JSONObject();
                try {
                    driverData.put("id", id);
                } catch (JSONException e) {
                    e.printStackTrace();
                    if (getActivity() != null) {
                        getActivity().runOnUiThread(() -> 
                            Toast.makeText(getContext(), "Error preparing data", Toast.LENGTH_LONG).show()
                        );
                    }
                    return;
                }

                OutputStream os = conn.getOutputStream();
                BufferedWriter writer = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));
                writer.write(driverData.toString());
                writer.flush();
                writer.close();
                os.close();

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
                        if (getActivity() != null) {
                            getActivity().runOnUiThread(() -> {
                                Toast.makeText(getContext(), "Driver deleted successfully", Toast.LENGTH_SHORT).show();
                                loadDrivers();
                            });
                        }
                    } else {
                        String message = response.optString("message", "Unknown error occurred");
                        if (getActivity() != null) {
                            getActivity().runOnUiThread(() -> 
                                Toast.makeText(getContext(), "Error: " + message, Toast.LENGTH_LONG).show()
                            );
                        }
                    }
                } catch (JSONException e) {
                    e.printStackTrace();
                    if (getActivity() != null) {
                        getActivity().runOnUiThread(() -> 
                            Toast.makeText(getContext(), "Error parsing server response", Toast.LENGTH_LONG).show()
                        );
                    }
                }

            } catch (Exception e) {
                e.printStackTrace();
                if (getActivity() != null) {
                    getActivity().runOnUiThread(() -> 
                        Toast.makeText(getContext(), "Network error: " + e.getMessage(), Toast.LENGTH_LONG).show()
                    );
                }
            }
        }).start();
    }

    private void loadDrivers() {
        new Thread(() -> {
            try {
                URL url = new URL(DRIVERS_URL);
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
                        JSONArray driversArray = response.getJSONArray("drivers");
                        List<Driver> drivers = new ArrayList<>();

                        for (int i = 0; i < driversArray.length(); i++) {
                            try {
                                JSONObject driverObj = driversArray.getJSONObject(i);
                                Driver driver = new Driver(
                                    driverObj.optInt("id", 0),
                                    driverObj.optString("username", ""),
                                    driverObj.optString("email", ""),
                                    driverObj.optString("license_number", ""),
                                    driverObj.optString("license_expiry", ""),
                                    driverObj.optString("contact_number", ""),
                                    driverObj.optString("address", ""),
                                    driverObj.optString("status", "inactive")
                                );
                                drivers.add(driver);
                            } catch (JSONException e) {
                                e.printStackTrace();
                                // Continue to next item if one fails
                                continue;
                            }
                        }

                        if (getActivity() != null) {
                            getActivity().runOnUiThread(() -> {
                                driverList.clear();
                                driverList.addAll(drivers);
                                driverAdapter.notifyDataSetChanged();
                            });
                        }
                    } else {
                        String message = response.optString("message", "Unknown error occurred");
                        if (getActivity() != null) {
                            getActivity().runOnUiThread(() -> 
                                Toast.makeText(getContext(), "Error loading drivers: " + message, Toast.LENGTH_LONG).show()
                            );
                        }
                    }
                } catch (JSONException e) {
                    e.printStackTrace();
                    if (getActivity() != null) {
                        getActivity().runOnUiThread(() -> 
                            Toast.makeText(getContext(), "Error parsing server response", Toast.LENGTH_LONG).show()
                        );
                    }
                }

            } catch (Exception e) {
                e.printStackTrace();
                if (getActivity() != null) {
                    getActivity().runOnUiThread(() -> 
                        Toast.makeText(getContext(), "Network error: " + e.getMessage(), Toast.LENGTH_LONG).show()
                    );
                }
            }
        }).start();
    }

    // Driver model class
    public static class Driver {
        int id;
        String username;
        String email;
        String licenseNumber;
        String licenseExpiry;
        String contactNumber;
        String address;
        String status;

        Driver(int id, String username, String email, String licenseNumber, String licenseExpiry, 
              String contactNumber, String address, String status) {
            this.id = id;
            this.username = username;
            this.email = email;
            this.licenseNumber = licenseNumber;
            this.licenseExpiry = licenseExpiry;
            this.contactNumber = contactNumber;
            this.address = address;
            this.status = status;
        }
    }
} 