#!/usr/bin/env python3
import argparse
import requests
import sys
import json
import socket
import time
import platform
from datetime import datetime
from subprocess import Popen, PIPE
import subprocess


# Nagios exit codes
STATE_OK = 0
STATE_WARNING = 1
STATE_CRITICAL = 2
STATE_UNKNOWN = 3

def check_os():
    current_platform = platform.system().lower()
    if current_platform == 'windows':
        return 'windows'
    elif current_platform == 'linux':
        return 'linux'
    elif current_platform == 'darwin':
        return 'macos'
    else:
        return 'unknown'

def query_zabbix_agent(host_ip, key):
    request_data = b"ZBXD\x01" + len(key).to_bytes(8, byteorder="little") + key.encode()
    try:
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
            sock.settimeout(10)
            sock.connect((host_ip, 10050))
            sock.sendall(request_data)
            response_data = b""

            while True:
                chunk = sock.recv(1024)
                if not chunk:
                    break
                response_data += chunk

        if not response_data:
            raise ValueError("No data received from Zabbix agent")

        response = response_data[13:].decode().strip()
        return response

    except (socket.timeout, socket.error, ValueError) as e:
        print(f"CRITICAL: Failed to query '{key}' from agent at {host_ip} - {e}")
        sys.exit(STATE_CRITICAL)

def check_threshold(value, warning_threshold, critical_threshold, metric_name):
    if critical_threshold is not None and value >= critical_threshold:
        print(f"CRITICAL: {metric_name} exceeds critical threshold ({value:.2f}) | {metric_name}={value:.2f}")
        sys.exit(STATE_CRITICAL)
    elif warning_threshold is not None and value >= warning_threshold:
        print(f"WARNING: {metric_name} exceeds warning threshold ({value:.2f}) | {metric_name}={value:.2f}")
        sys.exit(STATE_WARNING)
    else:
        print(f"OK: {metric_name} is within acceptable limits ({value:.2f}) | {metric_name}={value:.2f}")
        sys.exit(STATE_OK)

# Updated functions for formatting the output
def format_cpu_usage(cpu_usage):
    return f"{cpu_usage:.2f}%"

def format_memory_usage(memory_bytes):
    if memory_bytes >= 1024**3:
        return f"{memory_bytes / (1024**3):.2f} GB"
    elif memory_bytes >= 1024**2:
        return f"{memory_bytes / (1024**2):.2f} MB"
    else:
        return f"{memory_bytes:.2f} bytes"

def format_disk_usage(disk_usage):
    if disk_usage >= 1024**3:
        return f"{disk_usage / (1024**3):.2f} GB"
    elif disk_usage >= 1024**2:
        return f"{disk_usage / (1024**2):.2f} MB"
    else:
        return f"{disk_usage:.2f} bytes"

def format_uptime(uptime_seconds):
    days = uptime_seconds // 86400
    hours = (uptime_seconds % 86400) // 3600
    minutes = (uptime_seconds % 3600) // 60
    if days > 0:
        return f"{days} days, {hours} hours, {minutes} minutes"
    elif hours > 0:
        return f"{hours} hours, {minutes} minutes"
    else:
        return f"{minutes} minutes"

def get_cpu_usage(host_ip, api_url, warning_threshold, critical_threshold):
    response = query_zabbix_agent(host_ip, "system.cpu.util[,idle]")
    try:
        cpu_idle = float(response)
        cpu_usage = 100.0 - cpu_idle
        cpu_usage_str = format_cpu_usage(cpu_usage)
        check_threshold(cpu_usage, warning_threshold, critical_threshold, "cpu_usage")
        print(f"OK: cpu_usage is within acceptable limits ({cpu_usage_str}) | cpu_usage={cpu_usage_str}")
    except ValueError:
        print(f"CRITICAL: Invalid CPU response from agent: '{response}'")
        sys.exit(STATE_CRITICAL)

def get_memory_usage(host_ip, api_url, warning_threshold, critical_threshold):
    response = query_zabbix_agent(host_ip, "vm.memory.size[available]")
    try:
        memory_available = int(response)  # in bytes
        memory_available_str = format_memory_usage(memory_available)
        check_threshold(memory_available, warning_threshold, critical_threshold, "available_memory")
        print(f"OK: available_memory is within acceptable limits ({memory_available_str}) | available_memory={memory_available_str}")
    except ValueError:
        print(f"CRITICAL: Invalid memory response from agent: '{response}'")
        sys.exit(STATE_CRITICAL)

def get_disk_space_usage(host_ip, api_url, warning_threshold, critical_threshold):
    response = query_zabbix_agent(host_ip, "vfs.fs.size[/,pused]")
    try:
        disk_usage = float(response)
        disk_usage_str = format_disk_usage(disk_usage)
        check_threshold(disk_usage, warning_threshold, critical_threshold, "disk_usage")
        print(f"OK: disk_usage is within acceptable limits ({disk_usage_str}) | disk_usage={disk_usage_str}")
    except ValueError:
        print(f"CRITICAL: Invalid disk space response from agent: '{response}'")
        sys.exit(STATE_CRITICAL)

def get_system_uptime(host_ip, api_url, warning_threshold, critical_threshold):
    response = query_zabbix_agent(host_ip, "system.uptime")
    try:
        uptime = float(response)  # in seconds
        uptime_str = format_uptime(uptime)
        check_threshold(uptime, warning_threshold, critical_threshold, "uptime")
        print(f"OK: uptime is within acceptable limits ({uptime_str}) | uptime={uptime_str}")
    except ValueError:
        print(f"CRITICAL: Invalid uptime response from agent: '{response}'")
        sys.exit(STATE_CRITICAL)

def get_network_interface():
    os_type = platform.system().lower()

    if os_type == 'linux' or os_type == 'darwin':
        try:
            result = subprocess.check_output("ip a | grep 'state UP' | head -n 1", shell=True).decode().strip()
            interface_name = result.split(":")[1].strip()
        except Exception as e:
            print(f"CRITICAL: Failed to detect network interface on {os_type} - {e}")
            sys.exit(STATE_CRITICAL)
    
    elif os_type == 'windows':
        try:
            result = subprocess.check_output("netsh interface show interface", shell=True).decode().strip()
            interface_name = result.splitlines()[3].split()[3]
        except Exception as e:
            print(f"CRITICAL: Failed to detect network interface on Windows - {e}")
            sys.exit(STATE_CRITICAL)
    
    else:
        print(f"CRITICAL: Unsupported OS: {os_type}")
        sys.exit(STATE_CRITICAL)
    
    return interface_name

def get_network_in_traffic(host_ip, api_url, warning_threshold, critical_threshold):
    interface_name = get_network_interface()
    key = f"net.if.in[{interface_name}]"
    
    response = query_zabbix_agent(host_ip, key)
    try:
        net_in = float(response)
        check_threshold(net_in, warning_threshold, critical_threshold, "net_in_traffic")
    except ValueError:
        print(f"CRITICAL: Invalid network in traffic response from agent: '{response}'")
        sys.exit(STATE_CRITICAL)

def get_network_out_traffic(host_ip, api_url, warning_threshold, critical_threshold):
    interface_name = get_network_interface()
    key = f"net.if.out[{interface_name}]"
    
    response = query_zabbix_agent(host_ip, key)
    try:
        net_out = float(response)
        check_threshold(net_out, warning_threshold, critical_threshold, "net_out_traffic")
    except ValueError:
        print(f"CRITICAL: Invalid network out traffic response from agent: '{response}'")
        sys.exit(STATE_CRITICAL)

def get_process_count(host_ip, api_url, warning_threshold, critical_threshold):
    response = query_zabbix_agent(host_ip, "proc.num")
    try:
        proc_count = int(response)
        check_threshold(proc_count, warning_threshold, critical_threshold, "process_count")
    except ValueError:
        print(f"CRITICAL: Invalid process count response from agent: '{response}'")
        sys.exit(STATE_CRITICAL)

def get_system_cpu_load(host_ip, api_url, warning_threshold, critical_threshold):
    response = query_zabbix_agent(host_ip, "system.cpu.load[all,avg1]")
    try:
        cpu_load = float(response)
        check_threshold(cpu_load, warning_threshold, critical_threshold, "cpu_load")
        print(f"OK: cpu_load is within acceptable limits ({cpu_load:.2f}) | cpu_load={cpu_load:.2f}")
    except ValueError:
        print(f"CRITICAL: Invalid CPU load response from agent: '{response}'")
        sys.exit(STATE_CRITICAL)

def get_system_hostname(host_ip, api_url, warning_threshold, critical_threshold):
    response = query_zabbix_agent(host_ip, "system.hostname")
    print(f"OK: Hostname: {response}")
    sys.exit(STATE_OK)

# Main check function
def run_os_specific_check(host_ip, check_type, api_url, warning_threshold, critical_threshold):
    os_type = check_os()
    if os_type == 'windows':
        print("Windows checks are not implemented.")
        sys.exit(STATE_UNKNOWN)
    elif os_type in ['linux', 'macos']:
        if check_type == 'cpu':
            get_cpu_usage(host_ip, api_url, warning_threshold, critical_threshold)
        elif check_type == 'memory':
            get_memory_usage(host_ip, api_url, warning_threshold, critical_threshold)
        elif check_type == 'disk':
            get_disk_space_usage(host_ip, api_url, warning_threshold, critical_threshold)
        elif check_type == 'uptime':
            get_system_uptime(host_ip, api_url, warning_threshold, critical_threshold)
        elif check_type == 'net_in':
            get_network_in_traffic(host_ip, api_url, warning_threshold, critical_threshold)
        elif check_type == 'net_out':
            get_network_out_traffic(host_ip, api_url, warning_threshold, critical_threshold)
        elif check_type == 'process_count':
            get_process_count(host_ip, api_url, warning_threshold, critical_threshold)
        elif check_type == 'cpu_load':
            get_system_cpu_load(host_ip, api_url, warning_threshold, critical_threshold)
        elif check_type == 'hostname':
            get_system_hostname(host_ip, api_url, warning_threshold, critical_threshold)
        else:
            print(f"CRITICAL: Unsupported check type {check_type} for OS {platform.system()}")
            sys.exit(STATE_UNKNOWN)
    else:
        print(f"CRITICAL: Unsupported OS type {os_type}")
        sys.exit(STATE_UNKNOWN)

def main():
    parser = argparse.ArgumentParser(description="Nagios plugin to monitor Zabbix resources via agent or system checks")
    parser.add_argument("-H", "--host", required=True, help="Host IP to monitor")
    parser.add_argument("--check", choices=["cpu", "memory", "disk", "uptime", "net_in", "net_out", "process_count", "cpu_load", "hostname"], required=True, help="Check type")
    parser.add_argument("--warning", type=float, help="Warning threshold")
    parser.add_argument("--critical", type=float, help="Critical threshold")
    parser.add_argument("--api-url", help="API URL for Zabbix (optional)")
    args = parser.parse_args()

    run_os_specific_check(args.host, args.check, args.api_url, args.warning, args.critical)

if __name__ == "__main__":
    main()