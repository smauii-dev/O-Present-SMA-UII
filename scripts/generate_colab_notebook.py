#!/usr/bin/env python3
"""Generate Google Colab notebook for attendance analysis."""

import json

notebook = {
    "nbformat": 4,
    "nbformat_minor": 0,
    "metadata": {
        "colab": {"provenance": [], "authorship_tag": "ABX9TyN...", "include_colab_link": True},
        "kernelspec": {"name": "python3", "display_name": "Python 3"},
        "language_info": {"name": "python"}
    },
    "cells": [
        {
            "cell_type": "markdown",
            "metadata": {},
            "source": [
                "# 📊 O-Present SMA UII - Attendance Analysis Dashboard\n",
                "\n",
                "Auto-generated from simulation data. Upload the CSV to Colab or mount Drive.\n",
                "\n",
                "```bash\n",
                "# In Colab:\n",
                "from google.colab import files\n",
                "uploaded = files.upload()  # Select attendance_simulation.csv\n",
                "```"
            ]
        },
        {
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": [
                "import pandas as pd\n",
                "import numpy as np\n",
                "import matplotlib.pyplot as plt\n",
                "import seaborn as sns\n",
                "from datetime import datetime\n",
                "\n",
                "sns.set_style('whitegrid')\n",
                "plt.rcParams['figure.figsize'] = (12, 6)\n",
                "\n",
                "# Load data\n",
                "df = pd.read_csv('attendance_simulation.csv')\n",
                "df['jam_masuk'] = pd.to_datetime(df['jam_masuk'], format='%H:%M:%S').dt.time\n",
                "df['jam_keluar'] = pd.to_datetime(df['jam_keluar'], format='%H:%M:%S').dt.time\n",
                "df['tanggal'] = pd.to_datetime(df['tanggal_masuk'])\n",
                "print(f'Total records: {len(df)}')\n",
                "print(f'Date range: {df[\"tanggal\"].min()} to {df[\"tanggal\"].max()}')\n",
                "print(f'Unique students: {df[\"nip\"].nunique()}')\n",
                "df.head()"
            ]
        },
        {
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": [
                "# Overall attendance summary\n",
                "total = len(df)\n",
                "on_time = (df['status_masuk'] == 'tepat_waktu').sum()\n",
                "late = (df['status_masuk'].str.contains('terlambat')).sum()\n",
                "early_leave = (df['status_keluar'] == 'pulang_cepat').sum()\n",
                "\n",
                "summary = pd.DataFrame({\n",
                "    'Metric': ['Total Records', 'On Time', 'Late', 'Early Leave', 'On-Time Rate', 'Late Rate'],\n",
                "    'Value': [total, on_time, late, early_leave, f'{on_time/total*100:.1f}%', f'{late/total*100:.1f}%']\n",
                "})\n",
                "summary"
            ]
        },
        {
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": [
                "# Daily attendance trend\n",
                "daily = df.groupby('tanggal').agg(\n",
                "    total=('nip', 'count'),\n",
                "    on_time=('status_masuk', lambda x: (x == 'tepat_waktu').sum()),\n",
                "    late=('status_masuk', lambda x: x.str.contains('terlambat').sum()),\n",
                "    avg_late_min=('terlambat_menit', 'mean')\n",
                ").reset_index()\n",
                "\n",
                "fig, axes = plt.subplots(2, 2, figsize=(14, 10))\n",
                "\n",
                "# Daily attendance\n",
                "axes[0,0].plot(daily['tanggal'], daily['total'], 'o-', label='Total')\n",
                "axes[0,0].plot(daily['tanggal'], daily['on_time'], 's-', label='On Time')\n",
                "axes[0,0].plot(daily['tanggal'], daily['late'], '^-', label='Late')\n",
                "axes[0,0].set_title('Daily Attendance Trend')\n",
                "axes[0,0].legend()\n",
                "axes[0,0].tick_params(axis='x', rotation=45)\n",
                "\n",
                "# Late minutes distribution\n",
                "late_df = df[df['terlambat_menit'] > 0]\n",
                "axes[0,1].hist(late_df['terlambat_menit'], bins=20, edgecolor='black', alpha=0.7)\n",
                "axes[0,1].set_title('Late Minutes Distribution')\n",
                "axes[0,1].set_xlabel('Minutes Late')\n",
                "axes[0,1].set_ylabel('Frequency')\n",
                "\n",
                "# On-time rate by student\n",
                "student_stats = df.groupby('nama').agg(\n",
                "    total=('nip', 'count'),\n",
                "    on_time=('status_masuk', lambda x: (x == 'tepat_waktu').sum()),\n",
                "    avg_late=('terlambat_menit', 'mean')\n",
                ").reset_index()\n",
                "student_stats['on_time_rate'] = student_stats['on_time'] / student_stats['total'] * 100\n",
                "axes[1,0].barh(student_stats['nama'], student_stats['on_time_rate'])\n",
                "axes[1,0].set_title('On-Time Rate by Student')\n",
                "axes[1,0].set_xlabel('On-Time Rate (%)')\n",
                "\n",
                "# Status pie chart\n",
                "status_counts = df['status_masuk'].value_counts()\n",
                "axes[1,1].pie(status_counts.values, labels=status_counts.index, autopct='%1.1f%%')\n",
                "axes[1,1].set_title('Attendance Status Distribution')\n",
                "\n",
                "plt.tight_layout()\n",
                "plt.show()"
            ]
        },
        {
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": [
                "# GPS Heatmap (requires folium)\n",
                "try:\n",
                "    import folium\n",
                "    from folium.plugins import HeatMap\n",
                "\n",
                "    center_lat = df['latitude_masuk'].mean()\n",
                "    center_lon = df['longitude_masuk'].mean()\n",
                "    \n",
                "    m = folium.Map(location=[center_lat, center_lon], zoom_start=16)\n",
                "    \n",
                "    heat_data = df[['latitude_masuk', 'longitude_masuk']].values.tolist()\n",
                "    HeatMap(heat_data, radius=15, blur=10).add_to(m)\n",
                "    \n",
                "    folium.Marker(\n",
                "        [center_lat, center_lon],\n",
                "        popup='SMA UII Yogyakarta',\n",
                "        icon=folium.Icon(color='red', icon='graduation-cap', prefix='fa')\n",
                "    ).add_to(m)\n",
                "    \n",
                "    m.save('attendance_heatmap.html')\n",
                "    print('Heatmap saved to attendance_heatmap.html')\n",
                "    \nexcept ImportError:\n",
                "    print('Install folium: !pip install folium')"
            ]
        },
        {
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": [
                "# Student detail report\n",
                "student_report = df.groupby(['nip', 'nama']).agg(\n",
                "    total_days=('tanggal', 'count'),\n",
                "    on_time=('status_masuk', lambda x: (x == 'tepat_waktu').sum()),\n",
                "    late=('status_masuk', lambda x: x.str.contains('terlambat').sum()),\n",
                "    early_leave=('status_keluar', lambda x: (x == 'pulang_cepat').sum()),\n",
                "    avg_late_min=('terlambat_menit', 'mean'),\n",
                "    avg_early_min=('pulang_cepat_menit', 'mean')\n",
                ").reset_index()\n",
                "\n",
                "student_report['on_time_rate'] = (student_report['on_time'] / student_report['total_days'] * 100).round(1)\n",
                "student_report = student_report.sort_values('on_time_rate')\n",
                "\n",
                "def highlight_low_rate(row):\n",
                "    if row['on_time_rate'] < 80:\n",
                "        return ['background-color: #ffcccc'] * len(row)\n",
                "    elif row['on_time_rate'] < 90:\n",
                "        return ['background-color: #fff3cd'] * len(row)\n",
                "    return [''] * len(row)\n",
                "\n",
                "styled = student_report.style.apply(highlight_low_rate, axis=1)\n",
                "styled"
            ]
        },
        {
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": [
                "# Export cleaned report\n",
                "student_report.to_csv('student_attendance_report.csv', index=False)\n",
                "print('Student report exported to student_attendance_report.csv')\n",
                "\n",
                "# Monthly summary\n",
                "df['month'] = df['tanggal'].dt.to_period('M')\n",
                "monthly = df.groupby('month').agg(\n",
                "    total=('nip', 'count'),\n",
                "    unique_students=('nip', 'nunique'),\n",
                "    on_time_rate=('status_masuk', lambda x: (x == 'tepat_waktu').mean() * 100),\n",
                "    late_rate=('status_masuk', lambda x: x.str.contains('terlambat').mean() * 100)\n",
                ").round(1)\n",
                "monthly"
            ]
        }
    ]
}

with open('/home/dev/web/instances/smauii/services/O-Present-SMA-UII-ori/tests/simulation/attendance_analysis.ipynb', 'w') as f:
    json.dump(notebook, f, indent=2)

print("✅ Generated Colab notebook")
