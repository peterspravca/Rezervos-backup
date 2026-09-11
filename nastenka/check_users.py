import pymysql
import json

try:
    connection = pymysql.connect(
        host='db1.usr.sk',
        user='vueto.sk',
        password='KHfgSjbar(819nNE',
        database='vueto',
        cursorclass=pymysql.cursors.DictCursor
    )
    with connection.cursor() as cursor:
        cursor.execute("SELECT id, username, full_name, email, role, is_active FROM crm_users")
        users = cursor.fetchall()
        print(json.dumps(users, indent=2))
except Exception as e:
    print("Error:", e)
finally:
    if 'connection' in locals():
        connection.close()
