#!/usr/bin/env python3
import argparse
import socket
import sys
import time
import re
STATE_OK = 0
STATE_WARNING = 1
STATE_CRITICAL = 2
STATE_UNKNOWN = 3
def query_zabbix_agent(host_ip, key):
    request_data = b"ZBXD\x01" + len(key).to_bytes(8, byteorder="little") + key.encode()
    try:
        with socket.create_connection((host_ip, 10050), timeout=10) as sock:
            sock.sendall(request_data)
            response_data = b""
            while True:
                chunk = sock.recv(1024)
                if not chunk:
                    break
                response_data += chunk
        if not response_data:
            raise ValueError("No data received from Zabbix agent")
        response = response_data[13:].decode(errors='ignore').strip()
        return response
    except Exception as e:
        print(f"CRITICAL: Failed to query '{key}' from agent at {host_ip} - {e}")
        sys.exit(STATE_CRITICAL)
def check_threshold(value, warning, critical, metric_name, units=""):
    if critical is not None and value >= critical:
        print(f"CRITICAL: {metric_name} {value:.2f}{units} exceeds critical threshold {critical}{units} | {metric_name}={value:.2f}{units}")
        sys.exit(STATE_CRITICAL)
    elif warning is not None and value >= warning:
        print(f"WARNING: {metric_name} {value:.2f}{units} exceeds warning threshold {warning}{units} | {metric_name}={value:.2f}{units}")
        sys.exit(STATE_WARNING)
    else:
        print(f"OK: {metric_name} is {value:.2f}{units} | {metric_name}={value:.2f}{units}")
        sys.exit(STATE_OK)
def get_cpu_usage(host_ip, warning, critical):
    try:
        resp = query_zabbix_agent(host_ip, "system.cpu.util[,system]")
        idle = float(resp)
        usage = 100.0 - idle
        check_threshold(usage, warning, critical, "cpu_usage", "%")
    except SystemExit:
        raise
    except Exception:
        print(f"CRITICAL: Invalid CPU response: '{resp}'")
        sys.exit(STATE_CRITICAL)
def get_memory_usage(host_ip, warning, critical):
    try:
        resp = query_zabbix_agent(host_ip, "vm.memory.size[available]")
        available = float(resp) / (1024 ** 3)  # Convert to GB
        check_threshold(available, warning, critical, "available_memory", "GB")
    except SystemExit:
        raise
    except Exception:
        print(f"CRITICAL: Invalid memory response: '{resp}'")
        sys.exit(STATE_CRITICAL)
def get_disk_usage(host_ip, warning, critical):
    try:
        resp = query_zabbix_agent(host_ip, "vfs.fs.size[/,pused]")
        disk = float(resp)
        check_threshold(disk, warning, critical, "disk_usage", "%")
    except SystemExit:
        raise
    except Exception:
        print(f"CRITICAL: Invalid disk usage response: '{resp}'")
        sys.exit(STATE_CRITICAL)
def get_uptime(host_ip, warning, critical):
    try:
        resp = query_zabbix_agent(host_ip, "system.uptime")
        uptime_seconds = float(resp)
        uptime_minutes = uptime_seconds / 60.0
        check_threshold(uptime_minutes, warning, critical, "uptime", "min")
    except SystemExit:
        raise
    except Exception:
        print(f"CRITICAL: Invalid uptime response: '{resp}'")
        sys.exit(STATE_CRITICAL)
def get_process_count(host_ip, warning, critical):
    try:
        resp = query_zabbix_agent(host_ip, "proc.num")
        count = float(resp)
        check_threshold(count, warning, critical, "process_count")
    except SystemExit:
        raise
    except Exception:
        print(f"CRITICAL: Invalid process count response: '{resp}'")
        sys.exit(STATE_CRITICAL)
def get_cpu_load(host_ip, warning, critical):
    try:
        resp = query_zabbix_agent(host_ip, "system.cpu.load[all,avg1]")
        load = float(resp)
        check_threshold(load, warning, critical, "cpu_load")
    except SystemExit:
        raise
    except Exception:
        print(f"CRITICAL: Invalid CPU load response: '{resp}'")
        sys.exit(STATE_CRITICAL)
def get_hostname(host_ip, warning, critical):
    try:
        resp = query_zabbix_agent(host_ip, "system.hostname")
        print(f"OK: Hostname: {resp} | hostname={resp}")
        sys.exit(STATE_OK)
    except SystemExit:
        raise
    except Exception:
        print("CRITICAL: Failed to get hostname.")
        sys.exit(STATE_CRITICAL)
def get_active_interfaces():
    interfaces = []
    try:
        with open("/proc/net/dev", "r") as f:
            data = f.readlines()
        for line in data[2:]:  # Skip the header lines
            match = re.match(r'\s*([^:]+):', line)
            if match:
                iface = match.group(1).strip()
                if iface != "lo":
                    interfaces.append(iface)
    except Exception as e:
        print(f"UNKNOWN: Unable to determine interfaces: {e}")
        sys.exit(STATE_UNKNOWN)
    return interfaces
def get_network_traffic_rate(host_ip, interface, direction, warning, critical):
    try:
        if not interface:
            print("CRITICAL: Must specify --interface for network checks.")
            sys.exit(STATE_CRITICAL)
        key = f"net.if.{direction}[{interface}]"
        val1 = query_zabbix_agent(host_ip, key)
        try:
            val1 = float(val1)
        except ValueError:
            print(f"CRITICAL: Invalid {direction} value from Zabbix agent: '{val1}'")
            sys.exit(STATE_CRITICAL)
        time.sleep(1)
        val2 = query_zabbix_agent(host_ip, key)
        try:
            val2 = float(val2)
        except ValueError:
            print(f"CRITICAL: Invalid {direction} value from Zabbix agent: '{val2}'")
            sys.exit(STATE_CRITICAL)
        rate = val2 - val1  # bytes/sec
        bits_per_sec = rate * 8
        mbps = bits_per_sec / 1_000_000
        check_threshold(mbps, warning, critical, f"net_{direction}_mbps[{interface}]", "Mbps")
    except SystemExit:
        raise
    except Exception:
        print(f"CRITICAL: Network {direction} check failed on {interface}.")
        sys.exit(STATE_CRITICAL)
def main():
    parser = argparse.ArgumentParser(description="Nagios plugin to monitor Zabbix agent metrics")
    parser.add_argument("-H", "--host", required=True, help="Host IP to monitor")
    parser.add_argument("--check", choices=[
        "cpu", "memory", "disk", "uptime", "net_in", "net_out", "process_count", "cpu_load", "hostname"
    ], required=True, help="Check type")
    parser.add_argument("--interface", help="Network interface name (required for net_in/net_out; otherwise auto-detects all)")
    parser.add_argument("--warning", type=float, help="Warning threshold")
    parser.add_argument("--critical", type=float, help="Critical threshold")
    args = parser.parse_args()
    if args.check in ["net_in", "net_out"]:
        direction = "in" if args.check == "net_in" else "out"
        if args.interface:
            get_network_traffic_rate(args.host, args.interface, direction, args.warning, args.critical)
        else:
            interfaces = get_active_interfaces()
            if not interfaces:
                print("CRITICAL: No active interfaces found.")
                sys.exit(STATE_CRITICAL)
            for iface in interfaces:
                try:
                    get_network_traffic_rate(args.host, iface, direction, args.warning, args.critical)
                except SystemExit as e:
                    sys.exit(e.code)
    elif args.check == "cpu":
        get_cpu_usage(args.host, args.warning, args.critical)
    elif args.check == "memory":
        get_memory_usage(args.host, args.warning, args.critical)
    elif args.check == "disk":
        get_disk_usage(args.host, args.warning, args.critical)
    elif args.check == "uptime":
        get_uptime(args.host, args.warning, args.critical)
    elif args.check == "process_count":
        get_process_count(args.host, args.warning, args.critical)
    elif args.check == "cpu_load":
        get_cpu_load(args.host, args.warning, args.critical)
    elif args.check == "hostname":
        get_hostname(args.host, args.warning, args.critical)
    else:
        print(f"UNKNOWN: Unsupported check type {args.check}")
        sys.exit(STATE_UNKNOWN)
if __name__ == "__main__":
    main()