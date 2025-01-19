import cv2
import pytesseract
import sys
import json
import re
import mysql.connector
from flask import Flask, render_template, request

# Set path for Tesseract executable (required if it's not in PATH)
pytesseract.pytesseract.tesseract_cmd = r'C:\Program Files\Tesseract-OCR\tesseract.exe'  # Update if necessary

# Open the camera
cap = cv2.VideoCapture(0)  # 0 is the default camera
if not cap.isOpened():
    print(json.dumps({"error": "Unable to access the camera."}))
    sys.exit()

print("Press 'C' to capture the receipt image.")

# Regular expression to match potential amounts (e.g., RM 12.50, $15.00, etc.)
amount_pattern = r'(RM|\$|\€)?\s?\d+(?:\.\d{2})?'

while True:
    ret, frame = cap.read()
    if not ret:
        print(json.dumps({"error": "Unable to read the camera stream."}))
        break

    cv2.imshow("Camera - Receipt Scanner", frame)

    # Wait for user input
    key = cv2.waitKey(1)
    if key == ord('c'):  # Press 'C' to capture the image
        captured_image_path = "captured_receipt.png"
        cv2.imwrite(captured_image_path, frame)
        print(json.dumps({"success": "Image captured", "path": captured_image_path}))
        break
    elif key == ord('q'):  # Press 'Q' to quit
        print(json.dumps({"error": "Exiting without capturing."}))
        cap.release()
        cv2.destroyAllWindows()
        sys.exit()

# Release the camera and close OpenCV windows
cap.release()
cv2.destroyAllWindows()

# Use Tesseract to extract text from the image
text = pytesseract.image_to_string(captured_image_path)

# Log the OCR result
print(json.dumps({"ocr_text": text}))

# Extract amount using regex
extracted_amount = None
match = re.search(amount_pattern, text)
if match:
    extracted_amount = match.group(0)

if extracted_amount:
    print(json.dumps({"success": "Amount extracted", "amount": extracted_amount}))
else:
    print(json.dumps({"error": "Unable to extract amount from the receipt."}))

# Add the extracted amount to the database
if extracted_amount:
    # Database connection
    conn = mysql.connector.connect(
        host="localhost",  # Update with your database host
        user="root",  # Update with your database username
        password="",  # Update with your database password
        database="transaction_db"  # Update with your database name
    )
    cursor = conn.cursor()

    # Insert the extracted amount into the database
    cursor.execute("INSERT INTO transactions (amount) VALUES (%s)", (extracted_amount,))
    conn.commit()

    # Close the database connection
    cursor.close()
    conn.close()

    print(json.dumps({"success": "Transaction added to database"}))

# Add the extracted amount to the database
if extracted_amount:
    try:
        # Convert extracted amount to a numeric value
        numeric_amount = float(re.sub(r'[^\d.]', '', extracted_amount))

        # Database connection
        conn = mysql.connector.connect(
            host="localhost",  # Update with your database host
            user="root",       # Update with your database username
            password="",       # Update with your database password
            database="transaction_db"  # Update with your database name
        )
        cursor = conn.cursor()

        # Insert the extracted transaction
        cursor.execute("""
            INSERT INTO Transactions (UserID, TranType, TranTitle, TranCat, TranAmount, TranDate)
            VALUES (%s, %s, %s, %s, %s, CURDATE())
        """, (1, 'Expense', 'Scanned Receipt', 'Uncategorized', numeric_amount))  # Update UserID and other fields as needed

        conn.commit()

        print(json.dumps({"success": "Transaction added to database"}))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
    finally:
        # Close the database connection
        cursor.close()
        conn.close()
