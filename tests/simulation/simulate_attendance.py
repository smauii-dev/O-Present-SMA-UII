#!/usr/bin/env python3
"""
O-Present Attendance Simulator - Uses REAL coordinates from database
Simulates student attendance for random dates around check-in/check-out times.
Generates CSV + Google Colab notebook for analysis.
"""

import random
import csv
import json
from datetime import date, datetime, timedelta, time
from dataclasses import dataclass, asdict
from typing import List

# ============================================================
# REAL COORDINATES FROM PRODUCTION DATABASE
# ID 3: SMA UII Yogyakarta | -7.81433 | 110.376034 | 500m radius
# ============================================================

SCHOOL_LAT = -7.81433
SCHOOL_LON = 110.376034
SCHOOL_RADIUS = 500  # meters
SCHOOL_JAM_MASUK = time(8, 0)
SCHOOL_JAM_PULANG = time(15, 30)

@dataclass
class Student:
    id: int
    nip: str
    nama: str
    id_lokasi: int

# Real students from production database (jabatan=4 = Siswa)
STUDENTS = [
    Student(2, "PEG-0001", "Jaya Wahyudi Putra", 1),
    Student(3, "PEG-0002", "Tamani Indah Permata", 1),
    Student(4, "PEG-0003", "Ahmad Hanif", 1),
    Student(5, "PEG-0018", "siswa14", 1),
    Student(6, "PEG-0019", "siswa15", 1),
    Student(7, "PEG-0020", "siswa17", 1),
    Student(8, "PEG-0021", "siswa18", 1),
    Student(9, "PEG-0022", "siswa20", 1),
    Student(10, "PEG-0023", "siswa22", 1),
]

@dataclass
class AttendanceRecord:
    nip: str
    nama: str
    tanggal_masuk: str
    jam_masuk: str
    foto_masuk: str
    latitude_masuk: float
    longitude_masuk: float
    tanggal_keluar: str
    jam_keluar: str
    foto_keluar: str
    latitude_keluar: float
    longitude_keluar: float
    status_masuk: str
    status_keluar: str
    terlambat_menit: int
    pulang_cepat_menit: int
    id_lokasi_presensi: int


# ============================================================
# SIMULATOR
# ============================================================

class AttendanceSimulator:
    def __init__(self, students: List = None):
        self.students = students or STUDENTS
        self.records: List[AttendanceRecord] = []
        
    
    def _random_time_around(self, base: time, sigma_minutes: int, 
                           earliest: time = None, latest: time = None) -> time:
        """Generate random time with normal distribution around base time."""
        base_minutes = base.hour * 60 + base.minute
        offset = int(random.gauss(0, sigma_minutes))
        result_minutes = base_minutes + offset
        
        if earliest:
            earliest_min = earliest.hour * 60 + earliest.minute
            result_minutes = max(result_minutes, earliest_min)
        if latest:
            latest_min = latest.hour * 60 + latest.minute
            result_minutes = min(result_minutes, latest_min)
            
        result_minutes = max(0, min(result_minutes, 23*60+59))
        return time(result_minutes // 60, result_minutes % 60)
    
    def _random_gps_around(self, base_lat: float, base_lon: float, 
                           radius_meters: int) -> tuple:
        """Generate random GPS coordinate within radius meters of base."""
        # 1 degree ≈ 111km, so 1 meter ≈ 0.000009 degrees
        max_deg = radius_meters / 111000.0
        dlat = random.uniform(-max_deg, max_deg)
        dlon = random.uniform(-max_deg, max_deg)
        return base_lat + dlat, base_lon + dlon
    
    def _get_status_masuk(self, scheduled: time, actual: time) -> tuple:
        """Determine check-in status and minutes late."""
        scheduled_min = scheduled.hour * 60 + scheduled.minute
        actual_min = actual.hour * 60 + actual.minute
        diff = actual_min - scheduled_min
        
        if diff <= 0:
            return "tepat_waktu", 0
        elif diff <= 15:
            return "terlambat_ringan", diff
        elif diff <= 30:
            return "terlambat_sedang", diff
        else:
            return "terlambat_berat", diff
    
    def _get_status_keluar(self, scheduled: time, actual: time) -> tuple:
        """Determine check-out status and minutes early."""
        scheduled_min = scheduled.hour * 60 + scheduled.minute
        actual_min = actual.hour * 60 + actual.minute
        diff = scheduled_min - actual_min
        
        if diff <= 0:
            return "tepat_waktu", 0
        elif diff <= 15:
            return "pulang_cepat_ringan", diff
        elif diff <= 30:
            return "pulang_cepat_sedang", diff
        else:
            return "pulang_cepat_berat", diff
    
    def simulate_attendance(self, target_date: date, attendance_rate: float = 0.92) -> List:
        """Simulate attendance for a single day."""
        records = []
        
        for student in self.students:
            if random.random() > attendance_rate:
                continue
                
            # Check-in time (normal distribution around jam_masuk, sigma=10 min)
            jam_masuk = self._random_time_around(
                SCHOOL_JAM_MASUK, 10, 
                earliest=time(6, 30), latest=time(9, 0)
            )
            
            # Check-out time (normal distribution around jam_pulang, sigma=15 min)
            jam_keluar = self._random_time_around(
                SCHOOL_JAM_PULANG, 15,
                earliest=time(13, 0), latest=time(17, 0)
            )
            
            # GPS coordinates with noise (within school radius)
            lat_masuk, lon_masuk = self._random_gps_around(
                SCHOOL_LAT, SCHOOL_LON, SCHOOL_RADIUS
            )
            lat_keluar, lon_keluar = self._random_gps_around(
                SCHOOL_LAT, SCHOOL_LON, SCHOOL_RADIUS
            )
            
            # Determine statuses
            status_masuk, terlambat = self._get_status_masuk(SCHOOL_JAM_MASUK, jam_masuk)
            status_keluar, pulang_cepat = self._get_status_keluar(SCHOOL_JAM_PULANG, jam_keluar)
            
            # Generate filenames
            date_str = target_date.strftime('%Y%m%d')
            nip_clean = student.nip.replace('-', '').replace('.', '')
            foto_masuk = f"presensi/{nip_clean}_{date_str}_masuk.jpg"
            foto_keluar = f"presensi/{nip_clean}_{date_str}_keluar.jpg"
            
            record = AttendanceRecord(
                nip=student.nip,
                nama=student.nama,
                tanggal_masuk=target_date.isoformat(),
                jam_masuk=jam_masuk.strftime('%H:%M:%S'),
                foto_masuk=foto_masuk,
                latitude_masuk=round(lat_masuk, 6),
                longitude_masuk=round(lon_masuk, 6),
                tanggal_keluar=target_date.isoformat(),
                jam_keluar=jam_keluar.strftime('%H:%M:%S'),
                foto_keluar=foto_keluar,
                latitude_keluar=round(lat_keluar, 6),
                longitude_keluar=round(lon_keluar, 6),
                status_masuk=status_masuk,
                status_keluar=status_keluar,
                terlambat_menit=terlambat,
                pulang_cepat_menit=pulang_cepat,
                id_lokasi_presensi=student.id_lokasi
            )
            records.append(record)
        
        self.records.extend(records)
        return records
    
    def simulate_range(self, start_date: date, end_date: date, attendance_rate: float = 0.92):
        """Simulate attendance for a date range (skips weekends)."""
        current = start_date
        while current <= end_date:
            if current.weekday() < 5:  # Skip weekends
                self.simulate_attendance(current, attendance_rate)
            current += timedelta(days=1)
    
    def export_csv(self, filename: str):
        if not self.records:
            print("No records to export")
            return
            
        fieldnames = list(asdict(self.records[0]).keys())
        with open(filename, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=fieldnames)
            writer.writeheader()
            for r in self.records:
                writer.writerow(asdict(r))
        print(f"✅ Exported {len(self.records)} records to {filename}")
    
    def export_json(self, filename: str):
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump([asdict(r) for r in self.records], f, indent=2, default=str)
        print(f"✅ Exported {len(self.records)} records to {filename}")
    
    def generate_summary(self) -> dict:
        if not self.records:
            return {}
            
        df = self.records
        total = len(df)
        on_time = sum(1 for r in df if r.status_masuk == 'tepat_waktu')
        late = sum(1 for r in df if 'terlambat' in r.status_masuk)
        early_leave = sum(1 for r in df if 'pulang_cepat' in r.status_keluar)
        
        late_records = [r for r in df if 'terlambat' in r.status_masuk]
        avg_late = sum(r.terlambat_menit for r in late_records) / len(late_records) if late_records else 0
        
        early_records = [r for r in df if 'pulang_cepat' in r.status_keluar]
        avg_early = sum(r.pulang_cepat_menit for r in early_records) / len(early_records) if early_records else 0
        
        return {
            'total_records': total,
            'on_time_count': on_time,
            'late_count': late,
            'early_leave_count': early_leave,
            'on_time_rate': round(on_time / total * 100, 1) if total else 0,
            'late_rate': round(late / total * 100, 1) if total else 0,
            'avg_late_minutes': round(avg_late, 1),
            'avg_early_minutes': round(avg_early, 1),
            'unique_students': len(set(r.nip for r in df)),
            'date_range': f"{min(r.tanggal_masuk for r in df)} to {max(r.tanggal_masuk for r in df)}",
            'school_coordinates': f"{SCHOOL_LAT}, {SCHOOL_LON}",
        }


# ============================================================
# GOOGLE COLAB NOTEBOOK GENERATOR
# ============================================================

def generate_colab_notebook(csv_filename='attendance_simulation.csv', 
                           output_filename='attendance_analysis.ipynb'):
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
                    "# GPS Heatmap - CORRECT COORDINATES\n",
                    "try:\n",
                    "    import folium\n",
                    "    from folium.plugins import HeatMap\n",
                    "    from IPython.display import HTML\n",
                    "\n",
                    "    # Use correct columns\n",
                    "    lat_col = 'latitude_masuk'\n",
                    "    lon_col = 'longitude_masuk'\n",
                    "    \n",
                    "    # Clean data\n",
                    "    heat_df = df[[lat_col, lon_col]].dropna()\n",
                    "    print(f\"Data points: {len(heat_df)}\")\n",
                    "    print(f\"Lat range: {heat_df[lat_col].min():.6f} to {heat_df[lat_col].max():.6f}\")\n",
                    "    print(f\"Lon range: {heat_df[lon_col].min():.6f} to {heat_df[lon_col].max():.6f}\")\n",
                    "    \n",
                    "    # Create map with CORRECT center (SMA UII Yogyakarta)\n",
                    "    center_lat = heat_df[lat_col].mean()\n",
                    "    center_lon = heat_df[lon_col].mean()\n",
                    "    \n",
                    "    m = folium.Map(\n",
                    "        location=[center_lat, center_lon], \n",
                    "        zoom_start=16,\n",
                    "        tiles='OpenStreetMap'\n",
                    "    )\n",
                    "\n",
                    "    HeatMap(\n",
                    "        heat_df[[lat_col, lon_col]].values.tolist(),\n",
                    "        radius=15,\n",
                    "        blur=10,\n",
                    "        max_zoom=18\n",
                    "    ).add_to(m)\n",
                    "\n",
                    "    folium.Marker(\n",
                    "    [center_lat, center_lon],\n",
                    "    popup='SMA UII Yogyakarta (-7.81433, 110.376034)',\n",
                    "    icon=folium.Icon(color='red', icon='graduation-cap', prefix='fa')\n",
                    ").add_to(m)\n",
                    "\n",
                    "    # DISPLAY IN COLAB\n",
                    "    from IPython.display import HTML\n",
                    "    HTML(m._repr_html_())\n",
                    "\n",
                    "except ImportError:\n",
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
    
    import json
    with open(output_filename, 'w') as f:
        json.dump(notebook, f, indent=2)
    print(f"✅ Generated Colab notebook: {output_filename}")


# ============================================================
# MAIN EXECUTION
# ============================================================

if __name__ == '__main__':
    import argparse
    from datetime import date, timedelta
    
    parser = argparse.ArgumentParser(description='O-Present Attendance Simulator (Real Coordinates)')
    parser.add_argument('--date', type=str, help='Single date (YYYY-MM-DD)')
    parser.add_argument('--start', type=str, help='Start date for range (YYYY-MM-DD)')
    parser.add_argument('--end', type=str, help='End date for range (YYYY-MM-DD)')
    parser.add_argument('--rate', type=float, default=0.92, help='Attendance rate (default 0.92)')
    parser.add_argument('--output', type=str, default='attendance_simulation.csv', help='Output CSV filename')
    parser.add_argument('--json', type=str, help='Also export JSON')
    parser.add_argument('--colab', action='store_true', help='Generate Google Colab notebook')
    parser.add_argument('--summary', action='store_true', help='Print summary stats')
    
    args = parser.parse_args()
    
    sim = AttendanceSimulator()
    
    if args.start and args.end:
        start = datetime.strptime(args.start, '%Y-%m-%d').date()
        end = datetime.strptime(args.end, '%Y-%m-%d').date()
        sim.simulate_range(start, end, args.rate)
    elif args.date:
        sim.simulate_attendance(datetime.strptime(args.date, '%Y-%m-%d').date(), args.rate)
    else:
        # Default: simulate last 5 weekdays
        end = date.today()
        start = end - timedelta(days=7)
        sim.simulate_range(start, end, args.rate)
    
    sim.export_csv(args.output)
    
    if args.json:
        sim.export_json(args.json)
        
    if args.colab:
        generate_colab_notebook(args.output)
        
    if args.summary:
        summary = sim.generate_summary()
        print("\n📊 SUMMARY")
        print(json.dumps(summary, indent=2, default=str))
