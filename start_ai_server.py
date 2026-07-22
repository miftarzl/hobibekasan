#!/usr/bin/env python3
import os
import sys
import subprocess
import time

def start_ai_server():
    """Start AI Flask Server"""
    print("🚀 Starting AI Recommendation Server...")
    
    # Check if API file exists
    api_file = "ai/random_recommendation_api.py"
    if not os.path.exists(api_file):
        print(f"❌ Error: {api_file} not found!")
        return False
    
    # Start Flask server
    try:
        print("📡 Starting Flask server on http://localhost:5000")
        print("🔄 API Endpoint: http://localhost:5000/api/random-recommendation/{user_id}")
        print("⏹ Press Ctrl+C to stop server")
        print("=" * 50)
        
        # Change to AI directory and start server
        os.chdir("ai")
        subprocess.run([sys.executable, "random_recommendation_api.py"], check=True)
        
    except KeyboardInterrupt:
        print("\n🛑 AI Server stopped by user")
    except Exception as e:
        print(f"❌ Error starting server: {e}")
        return False
    
    return True

if __name__ == "__main__":
    start_ai_server()
