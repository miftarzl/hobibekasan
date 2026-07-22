#!/usr/bin/env python3
"""
Show Products Database - Python Version
Menampilkan database produk dan AI analytics
"""

import mysql.connector
import sys

def show_products():
    """Menampilkan data produk"""
    try:
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='hobibekasan'
        )
        
        cursor = conn.cursor()
        
        # Get total products
        cursor.execute("SELECT COUNT(*) FROM products")
        total_products = cursor.fetchone()[0]
        
        print("=" * 60)
        print("PRODUCTS DATABASE - hobibekasan")
        print("=" * 60)
        print(f"Total Products: {total_products}")
        print()
        
        # Show products with stock
        cursor.execute("""
            SELECT product_id, name, price, stock, category_id 
            FROM products 
            WHERE stock > 0 
            ORDER BY created_at DESC 
            LIMIT 10
        """)
        
        products = cursor.fetchall()
        
        print("AVAILABLE PRODUCTS:")
        print("-" * 60)
        print(f"{'ID':<5} {'Name':<25} {'Price':<12} {'Stock':<6} {'Cat ID':<7}")
        print("-" * 60)
        
        for product in products:
            print(f"{product[0]:<5} {product[1][:24]:<25} {product[2]:<12.0f} {product[3]:<6} {product[4]:<7}")
        
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"Error: {e}")

def show_ai_data():
    """Menampilkan data AI analytics"""
    try:
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='hobibekasan'
        )
        
        cursor = conn.cursor()
        
        # Get AI analytics
        cursor.execute("SELECT COUNT(*) FROM ai_analytics")
        total_ai = cursor.fetchone()[0]
        
        print("\n" + "=" * 60)
        print("AI ANALYTICS DATABASE")
        print("=" * 60)
        print(f"Total AI Analytics: {total_ai}")
        print()
        
        # Show recent AI activity
        cursor.execute("""
            SELECT id, event_type, user_id, success, created_at 
            FROM ai_analytics 
            ORDER BY created_at DESC 
            LIMIT 10
        """)
        
        ai_data = cursor.fetchall()
        
        print("RECENT AI ACTIVITY:")
        print("-" * 60)
        print(f"{'ID':<5} {'Event Type':<20} {'User ID':<8} {'Success':<8} {'Created At':<20}")
        print("-" * 60)
        
        for row in ai_data:
            success = "YES" if row[3] else "NO"
            user_id = str(row[2]) if row[2] else "NULL"
            print(f"{row[0]:<5} {row[1][:19]:<20} {user_id:<8} {success:<8} {str(row[4]):<20}")
        
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"Error: {e}")

def show_categories():
    """Menampilkan data kategori"""
    try:
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='hobibekasan'
        )
        
        cursor = conn.cursor()
        
        cursor.execute("SELECT COUNT(*) FROM categories")
        total_categories = cursor.fetchone()[0]
        
        print("\n" + "=" * 60)
        print("CATEGORIES DATABASE")
        print("=" * 60)
        print(f"Total Categories: {total_categories}")
        print()
        
        cursor.execute("""
            SELECT category_id, name 
            FROM categories 
            ORDER BY name 
            LIMIT 10
        """)
        
        categories = cursor.fetchall()
        
        print("CATEGORIES:")
        print("-" * 30)
        print(f"{'ID':<5} {'Name':<25}")
        print("-" * 30)
        
        for cat in categories:
            print(f"{cat[0]:<5} {cat[1][:24]:<25}")
        
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"Error: {e}")

def show_database_summary():
    """Menampilkan summary semua database"""
    try:
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='hobibekasan'
        )
        
        cursor = conn.cursor()
        
        print("=" * 60)
        print("DATABASE SUMMARY - hobibekasan")
        print("=" * 60)
        
        # Get table counts
        tables = [
            ('products', 'Products'),
            ('categories', 'Categories'),
            ('ai_analytics', 'AI Analytics'),
            ('users', 'Users'),
            ('orders', 'Orders')
        ]
        
        for table_name, display_name in tables:
            try:
                cursor.execute(f"SELECT COUNT(*) FROM {table_name}")
                count = cursor.fetchone()[0]
                print(f"{display_name:<20}: {count:>10} records")
            except:
                print(f"{display_name:<20}: {'Error':>10}")
        
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"Error: {e}")

def main():
    """Main function"""
    if len(sys.argv) > 1:
        command = sys.argv[1].lower()
        
        if command == "products":
            show_products()
        elif command == "ai":
            show_ai_data()
        elif command == "categories":
            show_categories()
        elif command == "summary":
            show_database_summary()
        else:
            print("Command tidak dikenali")
            print("Usage: python show_products.py [products|ai|categories|summary]")
    else:
        # Default: show all
        show_database_summary()
        show_products()
        show_ai_data()
        show_categories()
        
    print("\n" + "=" * 60)
    print("Commands:")
    print("  python show_products.py products    - Show products only")
    print("  python show_products.py ai          - Show AI analytics only")
    print("  python show_products.py categories  - Show categories only")
    print("  python show_products.py summary     - Show database summary")
    print("=" * 60)

if __name__ == "__main__":
    main()
