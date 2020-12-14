from fastai.vision.all import Path, tensor, load_learner
import os
import sys

def get_model_path(model_name):
    file_path = os.path.dirname(os.path.realpath(__file__))
    dir_path = "/".join(file_path.split("/")[0:-1])
    models_path = dir_path+'/focusfinder/'
    return models_path + model_name

def get_model(model_name):
  return load_learner(get_model_path(model_name))

def get_focus_point(path_name):
        dfb = next(iter(df[df['name']==path_name.name].index), ('no match for '+path_name.name))
        return tensor([df['x_p'][dfb], df['y_p'][dfb]])

def predict_focus_point(model, image_path):
    # check if file is an img
    # TODO: Check if the path is a reasonable folder
    if not Path(image_path).is_file():
        print("ERROR")
        return None

    try:
        prediction = model.predict(image_path)
        return prediction[0][0]
    except Exception as e:
        print(e)
        return None
