FROM python:3.11-slim

WORKDIR /app

COPY docker/api/requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

COPY app.py /app/app.py

EXPOSE 5050
CMD ["gunicorn", "-b", "0.0.0.0:5050", "app:app"]