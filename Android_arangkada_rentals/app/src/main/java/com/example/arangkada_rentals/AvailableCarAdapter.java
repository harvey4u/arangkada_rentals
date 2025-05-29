package com.example.arangkada_rentals;

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
import java.util.Locale;

public class AvailableCarAdapter extends RecyclerView.Adapter<AvailableCarAdapter.CarViewHolder> {

    private List<Car> carList;
    private Context context;
    private OnCarClickListener listener;

    public interface OnCarClickListener {
        void onCarClick(Car car);
    }

    public AvailableCarAdapter(Context context, List<Car> carList, OnCarClickListener listener) {
        this.context = context;
        this.carList = carList;
        this.listener = listener;
    }

    @NonNull
    @Override
    public CarViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context)
                .inflate(R.layout.item_available_car, parent, false);
        return new CarViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull CarViewHolder holder, int position) {
        Car car = carList.get(position);
        
        // Set car name (make and model)
        holder.tvCarName.setText(String.format("%s %s", car.getMake(), car.getModel()));
        
        // Set car details (year and plate number)
        holder.tvCarDetails.setText(String.format("%d • %s", car.getYear(), car.getPlateNumber()));
        
        // Set price
        holder.tvPrice.setText(String.format(Locale.getDefault(), "₱%.2f per day", car.getPrice()));
        
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
        TextView tvCarName;
        TextView tvCarDetails;
        TextView tvPrice;

        CarViewHolder(@NonNull View itemView) {
            super(itemView);
            ivCarImage = itemView.findViewById(R.id.ivCarImage);
            tvCarName = itemView.findViewById(R.id.tvCarName);
            tvCarDetails = itemView.findViewById(R.id.tvCarDetails);
            tvPrice = itemView.findViewById(R.id.tvPrice);
        }
    }
} 