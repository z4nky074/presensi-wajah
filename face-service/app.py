import os

import cv2
import face_recognition
import numpy as np
from flask import Flask, jsonify, request

app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = 6 * 1024 * 1024  # batas upload 6 MB

MAX_SIDE = 640          # citra diperkecil agar deteksi cepat di PC biasa
DETECT_MODEL = "hog"    # "hog" ringan (CPU); "cnn" lebih akurat tapi berat


def muat_gambar(file_storage):
    data = np.frombuffer(file_storage.read(), dtype=np.uint8)
    bgr = cv2.imdecode(data, cv2.IMREAD_COLOR)
    if bgr is None:
        return None
    h, w = bgr.shape[:2]
    skala = MAX_SIDE / max(h, w)
    if skala < 1:
        bgr = cv2.resize(bgr, (int(w * skala), int(h * skala)), interpolation=cv2.INTER_AREA)
    return cv2.cvtColor(bgr, cv2.COLOR_BGR2RGB)


@app.get("/health")
def health():
    return jsonify(status="ok", model=DETECT_MODEL)


@app.post("/encode")
def encode():
    file = request.files.get("image")
    if file is None:
        return jsonify(ok=False, code="bad_request", message="Field 'image' tidak ditemukan."), 400

    rgb = muat_gambar(file)
    if rgb is None:
        return jsonify(ok=False, code="bad_image", message="Berkas bukan citra yang valid."), 400

    lokasi = face_recognition.face_locations(rgb, model=DETECT_MODEL)
    if len(lokasi) == 0:
        return jsonify(ok=False, code="no_face", message="Wajah tidak terdeteksi.")
    if len(lokasi) > 1:
        return jsonify(ok=False, code="multiple_faces",
                       message="Terdeteksi lebih dari satu wajah.", faces=len(lokasi))

    encoding = face_recognition.face_encodings(rgb, known_face_locations=lokasi)[0]
    return jsonify(ok=True, code="ok", faces=1, encoding=[float(x) for x in encoding])


if __name__ == "__main__":
    app.run(host=os.environ.get("HOST", "127.0.0.1"), port=int(os.environ.get("PORT", "5000")))