# ai_service/app.py
from flask import Flask, request, jsonify
from PIL import Image
import os
import cv2 # OpenCV

app = Flask(__name__)

# Define the upload folder for images processed by the AI service (can be different from PHP's upload)
# For this example, let's assume PHP uploads to a shared or known location.
# PHP_UPLOAD_FOLDER is the folder where PHP saves the images.
# Ensure this path is correct based on your project structure.
# If ai_service is outside the webroot, this might be an absolute path
# or a relative path assuming a certain deployment structure.
# For simplicity, let's assume PHP uploads to '../uploads/' (relative to ai_service directory)
# THIS PATH IS CRITICAL AND LIKELY NEEDS ADJUSTMENT by the user.
PHP_UPLOAD_FOLDER = os.path.join(os.path.dirname(__file__), '..', 'uploads')


@app.route('/analyze_image', methods=['POST'])
def analyze_image_route():
    if 'image_path' not in request.json:
        return jsonify({'error': 'No image_path provided in JSON payload'}), 400

    relative_image_path = request.json['image_path'] # e.g., "my_laptop_image.jpg"

    # Construct the full path. IMPORTANT: This assumes 'relative_image_path' is just the filename
    # and it exists directly in PHP_UPLOAD_FOLDER.
    # We need to be careful about path traversal here in a real app.
    image_filename = os.path.basename(relative_image_path)
    full_image_path = os.path.join(PHP_UPLOAD_FOLDER, image_filename)

    if not os.path.exists(full_image_path):
        app.logger.error(f"Image not found at: {full_image_path}")
        # Simple fallback for common case where PHP_UPLOAD_FOLDER might be directly in project root
        # This is a guess, user MUST verify paths.
        alt_path = os.path.join(os.path.dirname(__file__), '..', '..', 'uploads', image_filename)
        if os.path.exists(alt_path):
            full_image_path = alt_path
        else:
            app.logger.error(f"Alternative path also not found: {alt_path}")
            return jsonify({'error': f'Image not found on server at path: {image_filename}. Expected in {PHP_UPLOAD_FOLDER} or {alt_path}'}), 404

    try:
        # Basic analysis using Pillow
        img = Image.open(full_image_path)
        img_format = img.format
        img_size = img.size
        img_mode = img.mode

        # Basic analysis using OpenCV (example: convert to grayscale and get dimensions)
        cv_img = cv2.imread(full_image_path)
        if cv_img is None:
            return jsonify({'error': 'OpenCV could not read the image. It might be corrupted or an unsupported format.'}), 400

        gray_img = cv2.cvtColor(cv_img, cv2.COLOR_BGR2GRAY)
        cv_dimensions = gray_img.shape # (height, width)

        # Simulate more complex AI analysis output
        simulated_components = [
            {'name': 'RAM Slot 1', 'status': 'OK', 'confidence': 0.95},
            {'name': 'SSD Connector', 'status': 'Possibly loose', 'confidence': 0.78},
            {'name': 'CPU Fan', 'status': 'Dusty', 'confidence': 0.90}
        ]

        suggested_steps = [
            "Check SSD connection.",
            "Consider cleaning CPU fan."
        ]

        analysis_data = {
            'filename': image_filename,
            'pillow_analysis': {
                'format': img_format,
                'size (width, height)': img_size,
                'mode': img_mode,
            },
            'opencv_analysis': {
                'dimensions (height, width)': cv_dimensions,
                'info': 'Successfully converted to grayscale.'
            },
            'simulated_ai_output': {
                'detected_components': simulated_components,
                'suggested_steps': suggested_steps,
                'message': "This is a basic analysis. More advanced component detection coming soon!"
            }
        }
        return jsonify(analysis_data), 200

    except FileNotFoundError:
        app.logger.error(f"Image not found during processing (should have been caught earlier): {full_image_path}")
        return jsonify({'error': 'Image file not found on server after initial check.'}), 404
    except Exception as e:
        app.logger.error(f"Error during image analysis: {e}")
        return jsonify({'error': f'An error occurred during analysis: {str(e)}'}), 500

if __name__ == '__main__':
    # Make sure to run on a port different from XAMPP's Apache (e.g., 5000)
    # Host '0.0.0.0' makes it accessible on the network, '127.0.0.1' for local only.
    app.run(host='127.0.0.1', port=5001, debug=True)
