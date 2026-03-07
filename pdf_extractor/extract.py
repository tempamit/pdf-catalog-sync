import pdfplumber
import json
import sys
import re

def clean_price(price_str):
    """Extracts the numeric value from a string like '1-9 pc Rs. 165'"""
    if not price_str:
        return None
    # Look for 'Rs.' followed by optional spaces and numbers
    match = re.search(r'Rs\.?\s*([\d,]+(?:\.\d+)?)', str(price_str), re.IGNORECASE)
    if match:
        clean_num = match.group(1).replace(',', '')
        return float(clean_num)
    return None

def extract_catalog_data(pdf_path):
    catalog_data = []
    current_category = "Uncategorized"

    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages:
            tables = page.extract_tables()

            # Extract hyperlinks on the page (to match 'See Image' and 'View Details')
            hyperlinks = page.hyperlinks

            for table in tables:
                for row in table:
                    # Clean the row to remove None types
                    clean_row = [str(cell).strip() if cell else "" for cell in row]

                    # Skip empty rows
                    if not any(clean_row):
                        continue

                    # DETECT CATEGORY: If the row has text in the first or second column but the rest is mostly empty
                    # Example: "A-TABLE CLOCKS"
                    if clean_row[0] and not clean_row[2] and not clean_row[6]:
                        current_category = clean_row[0]
                        continue

                    # DETECT PRODUCT: A valid product will have an SKU in column 0 and a Price in column 6 or 7
                    sku = clean_row[0]
                    if sku and sku.lower() != 'sku' and len(clean_row) >= 9:
                        item_name = clean_row[2]
                        colors = clean_row[3]

                        # Clean the prices using our regex function
                        sample_price = clean_price(clean_row[6])
                        bulk_price = clean_price(clean_row[7])
                        remarks = clean_row[8] if len(clean_row) > 8 else ""

                        # Note: In a production script, we would map the exact coordinates of the
                        # 'See Image' cell to the page.hyperlinks array to extract the exact URL.
                        # For this blueprint, we establish the placeholders.
                        image_link = "https://uni.ipds.cloud/placeholder.jpg"
                        detail_link = "https://uni.ipds.cloud/details"

                        product = {
                            "category_name": current_category,
                            "item_code": sku,
                            "item_name": item_name,
                            "colors_available": colors,
                            "image_link": image_link,
                            "detail_link": detail_link,
                            "sample_price": sample_price,
                            "bulk_price": bulk_price,
                            "comments": remarks
                        }
                        catalog_data.append(product)

    return catalog_data

if __name__ == "__main__":
    # Ensure the Laravel controller passed the file path
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No PDF path provided"}))
        sys.exit(1)

    pdf_file_path = sys.argv[1]

    try:
        extracted_data = extract_catalog_data(pdf_file_path)
        # Print as JSON so Laravel's shell_exec can capture it easily
        print(json.dumps(extracted_data))
    except Exception as e:
        print(json.dumps({"error": str(e)}))