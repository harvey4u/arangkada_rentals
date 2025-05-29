package com.example.arangkada_rentals;

import android.app.AlertDialog;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.PopupMenu;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import java.util.List;

public class    DriverAdapter extends RecyclerView.Adapter<DriverAdapter.DriverViewHolder> {

    private List<DriversFragment.Driver> driverList;
    private DriversFragment fragment;

    public DriverAdapter(List<DriversFragment.Driver> driverList) {
        this.driverList = driverList;
    }

    public void setFragment(DriversFragment fragment) {
        this.fragment = fragment;
    }

    @NonNull
    @Override
    public DriverViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_driver, parent, false);
        return new DriverViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull DriverViewHolder holder, int position) {
        DriversFragment.Driver driver = driverList.get(position);
        
        holder.tvDriverName.setText(driver.username);
        holder.tvDriverEmail.setText(driver.email);
        holder.tvLicenseNumber.setText(driver.licenseNumber);
        holder.tvLicenseExpiry.setText("License Expiry: " + driver.licenseExpiry);
        holder.tvStatus.setText(driver.status);

        // Set status color
        String status = driver.status.toLowerCase();
        if ("active".equals(status)) {
            holder.tvStatus.setTextColor(ContextCompat.getColor(holder.itemView.getContext(), android.R.color.holo_green_dark));
        } else if ("inactive".equals(status)) {
            holder.tvStatus.setTextColor(ContextCompat.getColor(holder.itemView.getContext(), android.R.color.holo_blue_dark));
        } else if ("suspended".equals(status)) {
            holder.tvStatus.setTextColor(ContextCompat.getColor(holder.itemView.getContext(), android.R.color.holo_orange_dark));
        }

        // Set up more options menu
        holder.btnMore.setOnClickListener(v -> {
            PopupMenu popup = new PopupMenu(holder.itemView.getContext(), holder.btnMore);
            popup.inflate(R.menu.driver_options_menu);
            
            popup.setOnMenuItemClickListener(item -> {
                int id = item.getItemId();
                if (id == R.id.action_edit) {
                    if (fragment != null) {
                        fragment.showDriverDialog(driver);
                    }
                    return true;
                } else if (id == R.id.action_delete) {
                    showDeleteConfirmation(driver);
                    return true;
                }
                return false;
            });
            
            popup.show();
        });
    }

    private void showDeleteConfirmation(DriversFragment.Driver driver) {
        new AlertDialog.Builder(fragment.requireContext())
            .setTitle("Delete Driver")
            .setMessage("Are you sure you want to delete this driver?")
            .setPositiveButton("Delete", (dialog, which) -> {
                if (fragment != null) {
                    fragment.deleteDriver(driver.id);
                }
            })
            .setNegativeButton("Cancel", null)
            .show();
    }

    @Override
    public int getItemCount() {
        return driverList.size();
    }

    static class DriverViewHolder extends RecyclerView.ViewHolder {
        TextView tvDriverName;
        TextView tvDriverEmail;
        TextView tvLicenseNumber;
        TextView tvLicenseExpiry;
        TextView tvStatus;
        ImageButton btnMore;

        DriverViewHolder(@NonNull View itemView) {
            super(itemView);
            tvDriverName = itemView.findViewById(R.id.tvDriverName);
            tvDriverEmail = itemView.findViewById(R.id.tvDriverEmail);
            tvLicenseNumber = itemView.findViewById(R.id.tvLicenseNumber);
            tvLicenseExpiry = itemView.findViewById(R.id.tvLicenseExpiry);
            tvStatus = itemView.findViewById(R.id.tvStatus);
            btnMore = itemView.findViewById(R.id.btnMore);
        }
    }
} 