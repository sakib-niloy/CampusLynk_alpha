import pandas as pd
import mysql.connector
import sys
import os

def main():
    if len(sys.argv) < 2:
        print("Usage: python import_exam_schedule.py <excel_file_path>")
        sys.exit(1)

    excel_path = sys.argv[1]
    
    try:
        df = pd.read_excel(excel_path)
    except FileNotFoundError:
        print(f"Error: The file '{excel_path}' was not found.")
        sys.exit(1)
    except Exception as e:
        print(f"Error reading Excel file: {e}")
        sys.exit(1)

    try:
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='university'
        )
        cursor = conn.cursor()

        # WARNING: This will delete all previous exam schedules before import
        cursor.execute('DELETE FROM exam_schedules')

        for _, row in df.iterrows():
            try:
                cursor.execute(
                    '''
                    INSERT INTO exam_schedules
                    (department, course_code, course_title, section, teacher, exam_date, exam_time, room)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    ''',
                    (
                        row['Dept.'],
                        row['Course Code'],
                        row['Course Title'],
                        row['Section'],
                        row['Teacher'],
                        pd.to_datetime(row['Exam Date']).date(),
                        row['Exam Time'],
                        str(row['Room'])
                    )
                )
            except mysql.connector.Error as err:
                print(f"Skipping row due to database error: {err}")
                continue

        conn.commit()
        cursor.close()
        conn.close()
        print("Exam schedule imported successfully.")

    except mysql.connector.Error as err:
        print(f"Database connection error: {err}")
        sys.exit(1)

if __name__ == "__main__":
    main() 