import pdfplumber
import json
import sys
import re

def clean_price(price_str):
    """Extracts the numeric value from a string like '1-9 pc Rs. 165'"""
    if not price_str:
        return None
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
            words = page.extract_words()
            hyperlinks = page.hyperlinks

            # X-RAY VISION: Extract all hidden links and find out what words are written on top of them
            page_links = []
            for hl in hyperlinks:
                uri = hl.get("uri")
                if not uri: continue

                link_text = ""
                for w in words:
                    # If the word's coordinates overlap with the invisible link's coordinates
                    if (w['x0'] <= hl['x1'] and w['x1'] >= hl['x0'] and
                        w['top'] <= hl['bottom'] and w['bottom'] >= hl['top']):
                        link_text += w['text'] + " "
                link_text = link_text.strip().lower()

                # Tag the link based on the words written over it
                link_type = "unknown"
                if "image" in link_text or "see" in link_text:
                    link_type = "image"
                elif "detail" in link_text or "view" in link_text:
                    link_type = "detail"

                page_links.append({
                    "type": link_type,
                    "uri": uri,
                    "top": hl['top'],
                    "bottom": hl['bottom'],
                    "x0": hl['x0'],
                    "center_y": (hl['top'] + hl['bottom']) / 2
                })

            for table in tables:
                for row in table:
                    clean_row = [str(cell).strip() if cell else "" for cell in row]
                    if not any(clean_row):
                        continue

                    if clean_row[0] and not clean_row[2] and not clean_row[6]:
                        current_category = clean_row[0]
                        continue

                    sku = clean_row[0]
                    if sku and sku.lower() != 'sku' and len(clean_row) >= 9:
                        item_name = clean_row[2]
                        colors = clean_row[3]
                        sample_price = clean_price(clean_row[6])
                        bulk_price = clean_price(clean_row[7])
                        remarks = clean_row[8] if len(clean_row) > 8 else ""

                        # Find the Y-coordinate (vertical position) of this product row
                        row_center_y = None
                        for w in words:
                            if w['text'] == sku or sku in w['text']:
                                row_center_y = (w['top'] + w['bottom']) / 2
                                break

                        image_link = None
                        detail_link = None

                        # Match the hidden links to this specific row
                        if row_center_y is not None:
                            row_links = []
                            for pl in page_links:
                                # If the link is on the same vertical line as the SKU (+/- 15 pixels)
                                if abs(pl['center_y'] - row_center_y) < 15:
                                    row_links.append(pl)

                            # Sort from left to right
                            row_links.sort(key=lambda x: x['x0'])

                            for pl in row_links:
                                if pl['type'] == 'image':
                                    image_link = pl['uri']
                                elif pl['type'] == 'detail':
                                    detail_link = pl['uri']

                            # Fallback: If text matching failed, assume left link is image, right link is details
                            if len(row_links) >= 2:
                                if not image_link: image_link = row_links[0]['uri']
                                if not detail_link: detail_link = row_links[1]['uri']

                        catalog_data.append({
                            "category_name": current_category,
                            "item_code": sku,
                            "item_name": item_name,
                            "colors_available": colors,
                            "image_link": image_link,
                            "detail_link": detail_link,
                            "sample_price": sample_price,
                            "bulk_price": bulk_price,
                            "comments": remarks
                        })

    return catalog_data

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No PDF path provided"}))
        sys.exit(1)

    pdf_file_path = sys.argv[1]

    try:
        extracted_data = extract_catalog_data(pdf_file_path)
        print(json.dumps(extracted_data))
    except Exception as e:
        print(json.dumps({"error": str(e)}))