#!/usr/bin/env python3
"""
Script untuk menjalankan Random Recommendation API
"""

import subprocess
import sys
import os
import time

def check_flask():
    """Check if Flask is installed"""
    try:
        import flask
        print(f"Flask found: {flask.__version__}")
        return True
    except ImportError:
        print("Flask not found. Installing...")
        subprocess.check_call([sys.executable, "-m", "pip", "install", "flask"])
        return True

def check_mysql_connector():
    """Check if MySQL connector is installed"""
    try:
        import mysql.connector
        print("MySQL connector found")
        return True
    except ImportError:
        print("MySQL connector not found. Installing...")
        subprocess.check_call([sys.executable, "-m", "pip", "install", "mysql-connector-python"])
        return True

def start_api():
    """Start the API server"""
    print("Starting Random Recommendation API...")
    print("=" * 50)
    
    # Check dependencies
    if not check_flask():
        print("Failed to install Flask")
        return
    
    if not check_mysql_connector():
        print("Failed to install MySQL connector")
        return
    
    print("\nDependencies OK!")
    print("Starting API server...")
    print("API will be available at: http://localhost:5000")
    print("Press Ctrl+C to stop the server")
    print("=" * 50)
    
    # Change to the correct directory
    script_dir = os.path.dirname(os.path.abspath(__file__))
    os.chdir(script_dir)
    
    # Start the Flask app
    try:
        subprocess.run([sys.executable, "random_recommendation_api.py"])
    except KeyboardInterrupt:
        print("\nAPI server stopped.")
    except Exception as e:
        print(f"Error starting API: {e}")

if __name__ == "__main__":
    start_api()
