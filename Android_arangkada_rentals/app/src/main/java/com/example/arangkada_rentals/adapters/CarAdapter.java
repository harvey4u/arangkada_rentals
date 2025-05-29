package com.example.arangkada_rentals.adapters;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.arangkada_rentals.R;
import com.example.arangkada_rentals.models.Car;

import java.util.List;
import java.text.NumberFormat;
import java.util.Locale;

public class CarAdapter extends RecyclerView.Adapter<CarAdapter.CarViewHolder> {

    private List<Car> carList;
    private Context context;
    private OnCarClickListener listener;

    public interface OnCarClickListener {
        void onCarClick(Car car);
    }

    public CarAdapter(Context context, List<Car> carList, OnCarClickListener listener) {
        this.context = context;
        this.carList = carList;
        this.listener = listener;
    }

    @NonNull
    @Override
    public CarViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context)
                .inflate(R.layout.item_car, parent, false);
        return new CarViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull CarViewHolder holder, int position) {
        Car car = carList.get(position);
        
        holder.tvMakeModel.setText(car.getMake() + " " + car.getModel());
        holder.tvYear.setText(String.valueOf(car.getYear()));
        holder.tvPlateNumber.setText(car.getPlateNumber());
        
        NumberFormat formatter = NumberFormat.getCurrencyInstance(new Locale("en", "PH"));
        holder.tvPrice.setText(formatter.format(car.getPrice()) + "/day");

        // Load car image if available
        if (car.getImageUrl() != null && !car.getImageUrl().isEmpty()) {
            Glide.with(context)
                .load(car.getImageUrl())
                .placeholder(R.drawable.car_placeholder)
                .error(R.drawable.car_placeholder)
                .into(holder.ivCarImage);
        } else {
            holder.ivCarImage.setImageResource(R.drawable.car_placeholder);
        }

        // Set status indicator
        int statusColor;
        switch (car.getStatus().toLowerCase()) {
            case "available":
                statusColor = context.getResources().getColor(R.color.available_green);
                break;
            case "rented":
                statusColor = context.getResources().getColor(R.color.rented_red);
                break;
            default:
                statusColor = context.getResources().getColor(R.color.maintenance_yellow);
                break;
        }
        holder.vStatusIndicator.setBackgroundColor(statusColor);

        holder.itemView.setOnClickListener(v -> {
            if (listener != null) {
                listener.onCarClick(car);
            }
        });
    }

    @Override
    public int getItemCount() {
        return carList.size();
    }

    public void updateList(List<Car> newList) {
        carList.clear();
        carList.addAll(newList);
        notifyDataSetChanged();
    }

    static class CarViewHolder extends RecyclerView.ViewHolder {
        ImageView ivCarImage;
        TextView tvMakeModel;
        TextView tvYear;
        TextView tvPlateNumber;
        TextView tvPrice;
        View vStatusIndicator;

        CarViewHolder(View itemView) {
            super(itemView);
            ivCarImage = itemView.findViewById(R.id.ivCarImage);
            tvMakeModel = itemView.findViewById(R.id.tvMakeModel);
            tvYear = itemView.findViewById(R.id.tvYear);
            tvPlateNumber = itemView.findViewById(R.id.tvPlateNumber);
            tvPrice = itemView.findViewById(R.id.tvPrice);
            vStatusIndicator = itemView.findViewById(R.id.vStatusIndicator);
        }
    }
} 