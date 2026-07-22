#!/usr/bin/env python3
"""
Super Simple Database Viewer - Tanpa dependencies
"""

import sys

def test_python():
    print("Testing Python...")
    try:
        import mysql.connector
        print("SUCCESS: mysql.connector imported")
        return True
    except ImportError as e:
        print(f"ERROR: {e}")
        print("Install: pip install mysql-connector-python")
        return False

def show_database():
    try:
        import mysql.connector
        
        # Connect
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='hobibekasan'
        )
        
        cursor = conn.cursor()
        
        # Get counts
        cursor.execute("SELECT COUNT(*) FROM products")
        products = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM ai_analytics")
        ai = cursor.fetchone()[0]
        
        print("=" * 50)
        print("DATABASE hobibekasan")
        print("=" * 50)
        print(f"Products: {products}")
        print(f"AI Analytics: {ai}")
        
        # Show products
        cursor.execute("SELECT name, price FROM products LIMIT 3")
        print("\nProducts:")
        for row in cursor.fetchall():
            print(f"  {row[0]} - Rp {row[1]:,}")
        
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"Database Error: {e}")
        print("Make sure XAMPP MySQL is running!")

if __name__ == "__main__":
    if test_python():
        show_database()
    else:
        print("\nTroubleshooting:")
        print("1. Install: pip install mysql-connector-python")
        print("2. Make sure XAMPP MySQL is running")
        print("3. Try: py simple_show.py")
