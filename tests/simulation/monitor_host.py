#!/usr/bin/env python3
"""
O-Present SMA UII - Comprehensive Host Performance Monitor
Collects system, Docker, and application metrics for remote monitoring via SSH/Colab.

Usage:
    # On host (one-time setup)
    chmod +x monitor_host.py
    
    # From Google Colab via SSH
    !ssh user@host "python3 /path/to/monitor_host.py --duration 300 --interval 10 --output /tmp/metrics.csv"
    
    # Or run continuously on host
    python3 monitor_host.py --daemon --interval 60 --output /var/log/host_metrics.csv
"""

import argparse
import csv
import json
import os
import subprocess
import sys
import time
import signal
import threading
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Optional, Any

# ============================================================
# CONFIGURATION
# ============================================================

DEFAULT_INTERVAL = 10  # seconds
DEFAULT_DURATION = 300  # seconds (5 minutes)
DEFAULT_OUTPUT = "/tmp/host_metrics.csv"

# Container names to monitor
CONTAINERS_TO_MONITOR = [
    "smauii-opresent-app",
    "smauii-rustfs",
    "nginx-proxy",
]

# Application endpoints to check
APP_ENDPOINTS = [
    ("presensi.smauiiyk.sch.id", 443),
    ("localhost", 80),  # Internal nginx-proxy
]

# ============================================================
# METRICS COLLECTORS
# ============================================================

def run_cmd(cmd: List[str], timeout: int = 5) -> Optional[str]:
    """Run command and return stdout, or None on error."""
    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=timeout)
        return result.stdout.strip() if result.returncode == 0 else None
    except Exception:
        return None


def get_system_metrics() -> Dict[str, Any]:
    """Collect system-level metrics."""
    metrics = {"timestamp": datetime.now().isoformat()}
    
    # CPU
    try:
        with open("/proc/stat") as f:
            line = f.readline()
            cpu_times = list(map(int, line.split()[1:]))
            total = sum(cpu_times)
            idle = cpu_times[3]
            metrics["cpu_idle_pct"] = round(idle / total * 100, 2)
            metrics["cpu_usage_pct"] = round(100 - metrics["cpu_idle_pct"], 2)
    except Exception:
        metrics["cpu_usage_pct"] = None
    
    # Memory
    try:
        with open("/proc/meminfo") as f:
            meminfo = {}
            for line in f:
                key, val = line.split(":")
                meminfo[key.strip()] = int(val.strip().split()[0])  # kB
        
        total = meminfo.get("MemTotal", 0)
        available = meminfo.get("MemAvailable", meminfo.get("MemFree", 0) + 
                               meminfo.get("Buffers", 0) + meminfo.get("Cached", 0))
        used = total - available
        metrics["mem_total_mb"] = round(total / 1024, 1)
        metrics["mem_used_mb"] = round(used / 1024, 1)
        metrics["mem_available_mb"] = round(available / 1024, 1)
        metrics["mem_usage_pct"] = round(used / total * 100, 2) if total else None
    except Exception:
        metrics["mem_usage_pct"] = None
    
    # Disk
    try:
        out = run_cmd(["df", "-B1", "/"])
        if out:
            parts = out.splitlines()[1].split()
            metrics["disk_total_gb"] = round(int(parts[1]) / 1e9, 2)
            metrics["disk_used_gb"] = round(int(parts[2]) / 1e9, 2)
            metrics["disk_free_gb"] = round(int(parts[3]) / 1e9, 2)
            metrics["disk_usage_pct"] = round(int(parts[2]) / int(parts[1]) * 100, 2)
    except Exception:
        pass
    
    # Load average
    try:
        with open("/proc/loadavg") as f:
            load = f.read().split()[:3]
            metrics["load_1m"] = float(load[0])
            metrics["load_5m"] = float(load[1])
            metrics["load_15m"] = float(load[2])
    except Exception:
        pass
    
    # Network (eth0)
    try:
        with open("/proc/net/dev") as f:
            for line in f:
                if "eth0:" in line or "ens" in line:
                    parts = line.split()
                    metrics["net_rx_mb"] = round(int(parts[1]) / 1e6, 2)
                    metrics["net_tx_mb"] = round(int(parts[9]) / 1e6, 2)
                    break
    except Exception:
        pass
    
    return metrics


def get_docker_metrics() -> List[Dict[str, Any]]:
    """Collect Docker container metrics."""
    results = []
    
    for container in CONTAINERS_TO_MONITOR:
        # Check if container exists and is running
        status = run_cmd(["docker", "inspect", "--format={{.State.Status}}", container])
        if status != "running":
            continue
        
        # Get stats (no stream, one shot)
        stats = run_cmd([
            "docker", "stats", "--no-stream", "--format",
            "{{.CPUPerc}},{{.MemUsage}},{{.MemPerc}},{{.NetIO}},{{.BlockIO}},{{.PIDs}}",
            container
        ])
        
        if stats:
            cpu_perc, mem_usage, mem_perc, net_io, block_io, pids = stats.split(",")
            
            # Parse memory (e.g., "150MiB / 2GiB")
            mem_parts = mem_usage.split(" / ")
            mem_used = mem_parts[0].strip() if len(mem_parts) > 0 else "0"
            mem_limit = mem_parts[1].strip() if len(mem_parts) > 1 else "0"
            
            results.append({
                "container": container,
                "cpu_pct": float(cpu_perc.replace("%", "")),
                "mem_usage": mem_used,
                "mem_limit": mem_limit,
                "mem_pct": float(mem_perc.replace("%", "")),
                "net_io": net_io,
                "block_io": block_io,
                "pids": int(pids),
            })
    
    return results


def get_app_health() -> List[Dict[str, Any]]:
    """Check application endpoints."""
    results = []
    
    for host, port in APP_ENDPOINTS:
        start = time.time()
        try:
            import socket
            sock = socket.create_connection((host, port), timeout=5)
            sock.close()
            latency_ms = round((time.time() - start) * 1000, 2)
            status = "up"
        except Exception:
            latency_ms = None
            status = "down"
        
        results.append({
            "endpoint": f"{host}:{port}",
            "status": status,
            "latency_ms": latency_ms,
        })
    
    return results


def get_db_metrics() -> Dict[str, Any]:
    """Get database connection pool stats (if accessible)."""
    # Try to get from app container
    out = run_cmd([
        "docker", "exec", "smauii-opresent-app",
        "php", "spark", "db:table", "ci_sessions"
    ])
    # This is a placeholder - actual DB metrics would need direct DB access
    return {"note": "DB metrics require direct DB connection"}


def collect_all_metrics() -> Dict[str, Any]:
    """Collect all metrics in one snapshot."""
    return {
        "system": get_system_metrics(),
        "containers": get_docker_metrics(),
        "endpoints": get_app_health(),
        "database": get_db_metrics(),
    }


# ============================================================
# CSV WRITER
# ============================================================

class MetricsCSVWriter:
    def __init__(self, filepath: str):
        self.filepath = Path(filepath)
        self.file = None
        self.writer = None
        self._header_written = False
        
    def __enter__(self):
        # Create directory if needed
        self.filepath.parent.mkdir(parents=True, exist_ok=True)
        
        # Check if file exists and has header
        file_exists = self.filepath.exists()
        if file_exists:
            with open(self.filepath, "r") as f:
                first_line = f.readline().strip()
                self._header_written = len(first_line) > 0
        
        self.file = open(self.filepath, "a", newline="")
        self.writer = csv.writer(self.file)
        
        if not self._header_written:
            self.writer.writerow([
                "timestamp",
                # System
                "cpu_usage_pct", "mem_usage_pct", "mem_used_mb", "mem_available_mb",
                "disk_usage_pct", "disk_used_gb", "disk_free_gb",
                "load_1m", "load_5m", "load_15m",
                "net_rx_mb", "net_tx_mb",
                # Containers (JSON)
                "containers_json",
                # Endpoints (JSON)
                "endpoints_json",
            ])
            self._header_written = True
        
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        if self.file:
            self.file.close()
    
    def write(self, metrics: Dict[str, Any]):
        ts = metrics["system"].get("timestamp", datetime.now().isoformat())
        
        sys_m = metrics["system"]
        containers_json = json.dumps(metrics["containers"])
        endpoints_json = json.dumps(metrics["endpoints"])
        
        self.writer.writerow([
            ts,
            sys_m.get("cpu_usage_pct"),
            sys_m.get("mem_usage_pct"),
            sys_m.get("mem_used_mb"),
            sys_m.get("mem_available_mb"),
            sys_m.get("disk_usage_pct"),
            sys_m.get("disk_used_gb"),
            sys_m.get("disk_free_gb"),
            sys_m.get("load_1m"),
            sys_m.get("load_5m"),
            sys_m.get("load_15m"),
            sys_m.get("net_rx_mb"),
            sys_m.get("net_tx_mb"),
            containers_json,
            endpoints_json,
        ])
        self.file.flush()


# ============================================================
# MAIN MONITOR CLASS
# ============================================================

class HostMonitor:
    def __init__(self, interval: int = DEFAULT_INTERVAL, output: str = DEFAULT_OUTPUT):
        self.interval = interval
        self.output = output
        self.running = False
        self._stop_event = threading.Event()
        
    def collect_once(self) -> Dict[str, Any]:
        """Collect metrics once and return."""
        print(f"[{datetime.now().isoformat()}] Collecting metrics...")
        return collect_all_metrics()
    
    def write_csv(self, metrics: Dict[str, Any]):
        with MetricsCSVWriter(self.output) as writer:
            writer.write(metrics)
    
    def run_duration(self, duration: int):
        """Run for specified duration (seconds)."""
        end_time = time.time() + duration
        print(f"Monitoring for {duration}s (interval: {self.interval}s)...")
        
        while time.time() < end_time and not self._stop_event.is_set():
            start = time.time()
            metrics = self.collect_once()
            self.write_csv(metrics)
            elapsed = time.time() - start
            sleep_time = max(0, self.interval - elapsed)
            time.sleep(sleep_time)
        
        print(f"Done. Data written to {self.output}")
    
    def run_daemon(self):
        """Run continuously until interrupted."""
        print(f"Starting daemon mode (interval: {self.interval}s). Press Ctrl+C to stop.")
        self.running = True
        
        def signal_handler(signum, frame):
            print("\nShutdown signal received...")
            self._stop_event.set()
        
        signal.signal(signal.SIGINT, signal_handler)
        signal.signal(signal.SIGTERM, signal_handler)
        
        while not self._stop_event.is_set():
            start = time.time()
            metrics = self.collect_once()
            self.write_csv(metrics)
            elapsed = time.time() - start
            sleep_time = max(0, self.interval - elapsed)
            self._stop_event.wait(sleep_time)
        
        print(f"\nStopped. Data written to {self.output}")


# ============================================================
# COLAB HELPER
# ============================================================

def generate_colab_ssh_command(host: str, user: str, duration: int = 300, 
                               interval: int = 10, output: str = "/tmp/metrics.csv") -> str:
    """Generate SSH command to run from Google Colab."""
    cmd = f"""ssh {user}@{host} "python3 /home/dev/web/instances/smauii/services/O-Present-SMA-UII-ori/tests/simulation/monitor_host.py --duration {duration} --interval {interval} --output {output}" """
    return cmd.strip()


# ============================================================
# MAIN
# ============================================================

def main():
    parser = argparse.ArgumentParser(
        description="O-Present Host Performance Monitor",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Run once and exit
  python3 monitor_host.py --once
  
  # Monitor for 5 minutes (300s) every 10s
  python3 monitor_host.py --duration 300 --interval 10 --output /tmp/metrics.csv
  
  # Run as daemon (continuous)
  python3 monitor_host.py --daemon --interval 60 --output /var/log/host_metrics.csv
  
  # From Google Colab via SSH:
  # !ssh user@host "python3 /path/monitor_host.py --duration 300 --interval 10 --output /tmp/metrics.csv"
        """
    )
    
    parser.add_argument("--once", action="store_true", help="Collect once and print JSON")
    parser.add_argument("--duration", type=int, default=DEFAULT_DURATION, 
                        help=f"Monitoring duration in seconds (default: {DEFAULT_DURATION})")
    parser.add_argument("--interval", type=int, default=DEFAULT_INTERVAL,
                        help=f"Collection interval in seconds (default: {DEFAULT_INTERVAL})")
    parser.add_argument("--output", type=str, default=DEFAULT_OUTPUT,
                        help=f"Output CSV file (default: {DEFAULT_OUTPUT})")
    parser.add_argument("--daemon", action="store_true", 
                        help="Run as continuous daemon (Ctrl+C to stop)")
    parser.add_argument("--colab-cmd", action="store_true",
                        help="Print SSH command for Google Colab")
    
    args = parser.parse_args()
    
    monitor = HostMonitor(interval=args.interval, output=args.output)
    
    if args.colab_cmd:
        # Print SSH command for Colab
        print("# Copy this to Google Colab:")
        print(f'!ssh user@your-host "python3 /home/dev/web/instances/smauii/services/O-Present-SMA-UII-ori/tests/simulation/monitor_host.py --duration {args.duration} --interval {args.interval} --output /tmp/metrics.csv"')
        return
    
    if args.once:
        metrics = monitor.collect_once()
        print(json.dumps(metrics, indent=2, default=str))
        return
    
    if args.daemon:
        monitor.run_daemon()
    else:
        monitor.run_duration(args.duration)


if __name__ == "__main__":
    main()