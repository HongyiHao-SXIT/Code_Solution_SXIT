import requests
import io
import zipfile
import json
import time

url_base = "http://127.0.0.1:5000"

zip_buffer = io.BytesIO()
with zipfile.ZipFile(zip_buffer, "w", zipfile.ZIP_DEFLATED) as zip_file:
    zip_file.writestr("data.yaml", "names: [test]\nnc: 1")
    zip_file.writestr("train/images/placeholder.txt", "")
    zip_file.writestr("val/images/placeholder.txt", "")
zip_buffer.seek(0)

files = {"dataset_zip": ("dataset.zip", zip_buffer, "application/zip")}
data = {"data_yaml": "data.yaml"}

print("--- POST /api/train/start ---")
try:
    response = requests.post(f"{url_base}/api/train/start", files=files, data=data)
    print(f"Status Code: {response.status_code}")
    resp_json = response.json()
    print(json.dumps(resp_json, indent=2))
    
    if response.status_code == 200 and "job_id" in resp_json:
        job_id = resp_json["job_id"]
        time.sleep(2)
        print(f"\n--- GET /api/train/status/{job_id} ---")
        status_resp = requests.get(f"{url_base}/api/train/status/{job_id}")
        print(f"Status Code: {status_resp.status_code}")
        status_json = status_resp.json()
        logs = status_json.get("logs", [])
        status_json["logs"] = logs[:3]
        print(json.dumps(status_json, indent=2))
except Exception as e:
    print(f"Error: {e}")
