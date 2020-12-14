from flask import Flask, request, jsonify
from urllib import parse
from prediction import get_focus_point, predict_focus_point, get_model

model_name = '20201129125255-10epochs-0.0005248074419796466trainrate_model.pkl'

model = get_model(model_name)

app = Flask(__name__)

@app.route('/')
def index():
    image_path = request.args.get('file', '')
    image_path = parse.unquote(image_path)
    focus_point = predict_focus_point(model, image_path)
    if focus_point is None:
        return jsonify(error=str('Image not found')), 404
    return jsonify([float(focus_point[0]) / 244, float(focus_point[1]) / 244])

# TODO: Only expose webserver on localhost
if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=8081, threaded=False)
