import os
import sys

pdf_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '../forms/rak facility.pdf'))
print("PDF Path:", pdf_path)

try:
    import pypdf
    reader = pypdf.PdfReader(pdf_path)
    text = ""
    for page in reader.pages:
        text += page.extract_text()
    print("--- Extracted via pypdf ---")
    print(text)
except Exception as e:
    print("pypdf error:", e)

try:
    import pdfplumber
    with pdfplumber.open(pdf_path) as pdf:
        text = ""
        for page in pdf.pages:
            text += page.extract_text()
        print("--- Extracted via pdfplumber ---")
        print(text)
except Exception as e:
    print("pdfplumber error:", e)
