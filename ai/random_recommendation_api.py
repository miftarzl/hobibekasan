from flask import Flask, jsonify
import mysql.connector
import random
from datetime import datetime
import os
import sys

app = Flask(__name__)

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'hobibekasan'
}

def get_db_connection():
    """Membuat koneksi ke database"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except Exception as e:
        print(f"Database connection error: {e}")
        return None

def get_random_product_for_user(user_id):
    """Mendapatkan produk acak untuk user dengan weighted logic"""
    conn = get_db_connection()
    if not conn:
        return None
    
    try:
        cursor = conn.cursor(dictionary=True)
        
        # Query untuk mendapatkan produk yang tersedia
        # Weighted random: produk dengan stok lebih tinggi punya kesempatan lebih besar
        query = """
        SELECT p.*, 
               (p.stock * 10) as weight_score,
               c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.stock > 0
        ORDER BY RAND() * weight_score DESC
        LIMIT 5
        """
        
        cursor.execute(query)
        products = cursor.fetchall()
        
        if not products:
            # Fallback: dapatkan produk apapun yang tersedia
            fallback_query = """
            SELECT p.* FROM products p
            WHERE p.stock > 0
            ORDER BY RAND()
            LIMIT 1
            """
            cursor.execute(fallback_query)
            products = cursor.fetchall()
        
        if products:
            # Pilih 1 produk secara acak dari top 5
            selected_product = random.choice(products)
            
            # Log user interaction untuk tracking
            log_user_interaction(cursor, user_id, selected_product['product_id'], 'recommendation_shown')
            
            conn.commit()
            cursor.close()
            conn.close()
            
            return selected_product
        
        cursor.close()
        conn.close()
        return None
        
    except Exception as e:
        print(f"Error getting random product: {e}")
        if conn:
            conn.close()
        return None

def log_user_interaction(cursor, user_id, product_id, interaction_type):
    """Mencatat interaksi user untuk analytics"""
    try:
        query = """
        INSERT INTO ai_analytics (user_id, product_id, event_type, event_data, created_at)
        VALUES (%s, %s, %s, %s, %s)
        """
        
        event_data = {
            'timestamp': datetime.now().isoformat(),
            'source': 'random_recommendation_api'
        }
        
        cursor.execute(query, (user_id, product_id, interaction_type, str(event_data), datetime.now()))
        
    except Exception as e:
        print(f"Error logging interaction: {e}")

@app.route('/api/random-recommendation/<int:user_id>')
def get_recommendation(user_id):
    """API endpoint untuk mendapatkan rekomendasi produk acak"""
    try:
        product = get_random_product_for_user(user_id)
        
        if product:
            return jsonify({
                'success': True,
                'product': {
                    'product_id': product['product_id'],
                    'name': product['name'],
                    'price': product['price'],
                    'image': product['image'],
                    'category_name': product.get('category_name', 'Uncategorized'),
                    'stock': product['stock'],
                    'description': product.get('description', '')[:100] + '...' if product.get('description') else ''
                },
                'message': f'Produk rekomendasi khusus untuk User {user_id}!',
                'timestamp': datetime.now().isoformat()
            })
        else:
            return jsonify({
                'success': False,
                'error': 'No products available',
                'message': 'Maaf, tidak ada produk tersedia saat ini.'
            }), 404
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e),
            'message': 'Terjadi kesalahan dalam memproses rekomendasi.'
        }), 500

@app.route('/api/health')
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'Random Recommendation API',
        'version': '1.0.0',
        'timestamp': datetime.now().isoformat()
    })

if __name__ == '__main__':
    print("Starting Random Recommendation API...")
    print("Available endpoints:")
    print("  GET /api/random-recommendation/<user_id>")
    print("  GET /api/health")
    print("\nAPI running on http://localhost:5000")
    
    app.run(debug=True, host='0.0.0.0', port=5000)
