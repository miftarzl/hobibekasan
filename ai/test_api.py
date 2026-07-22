#!/usr/bin/env python3
"""
Test API endpoint
"""

import requests
import json

def test_api():
    try:
        # Test API endpoint
        url = 'http://localhost:5000/api/random-recommendation/1?limit=1'
        print(f"Testing URL: {url}")
        
        response = requests.get(url, timeout=5)
        
        if response.status_code == 200:
            data = response.json()
            print("API Response:")
            print(json.dumps(data, indent=2))
            
            if data.get('success'):
                product = data.get('recommendations', [{}])[0]
                print(f"\nProduct: {product.get('name', 'Unknown')}")
                print(f"Price: {product.get('price', 0)}")
            else:
                print(f"Error: {data.get('error', 'Unknown error')}")
        else:
            print(f"HTTP Error: {response.status_code}")
            print(f"Response: {response.text}")
            
    except Exception as e:
        print(f"Connection Error: {e}")

if __name__ == "__main__":
    test_api()
