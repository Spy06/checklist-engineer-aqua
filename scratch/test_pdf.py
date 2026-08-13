import os
import sys

pdf_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '../forms/rolling door.pdf'))
print("PDF Path:", pdf_path)

try:
    import pypdf
    reader = pypdf.PdfReader(pdf_path)
    text = ""
    for page in reader.pages:
        text += page.extract_text()
    print("--- Extracted via pypdf ---")
    for line in text.split('\n'):
        if "RD" in line:
            print(line.strip())
except Exception as e:
    print("pypdf error:", e)

try:
    import pdfplumber
    with pdfplumber.open(pdf_path) as pdf:
        text = ""
        for page in pdf.pages:
            text += page.extract_text()
        print("--- Extracted via pdfplumber ---")
        for line in text.split('\n'):
            if "RD" in line:
                print(line.strip())
except Exception as e:
    print("pdfplumber error:", e)
