import os
import uuid

from werkzeug.utils import secure_filename


def ensure_training_roots(base_dir):
    training_root = os.path.join(base_dir, 'data', 'training')
    dataset_root = os.path.join(training_root, 'datasets')
    weights_root = os.path.join(training_root, 'weights')
    runs_root = os.path.join(training_root, 'runs')
    os.makedirs(dataset_root, exist_ok=True)
    os.makedirs(weights_root, exist_ok=True)
    os.makedirs(runs_root, exist_ok=True)
    return dataset_root, weights_root, runs_root


def init_job_dataset(dataset_zip, dataset_root):
    job_id = uuid.uuid4().hex
    job_dataset_dir = os.path.join(dataset_root, job_id)
    os.makedirs(job_dataset_dir, exist_ok=True)

    dataset_name = secure_filename(dataset_zip.filename) or 'dataset.zip'
    dataset_zip_path = os.path.join(job_dataset_dir, dataset_name)
    dataset_zip.save(dataset_zip_path)

    return job_id, job_dataset_dir, dataset_name, dataset_zip_path


def resolve_data_yaml(job_dataset_dir, raw_yaml):
    yaml_relative = (raw_yaml or 'data.yaml').strip().replace('\\', '/')
    yaml_path = os.path.normpath(os.path.join(job_dataset_dir, yaml_relative))
    if not yaml_path.startswith(os.path.abspath(job_dataset_dir)):
        raise ValueError('data_yaml 路径非法。')
    if not os.path.exists(yaml_path):
        raise ValueError(f'未在数据集中找到配置文件: {yaml_relative}')
    return yaml_relative, yaml_path


def resolve_weight_path(custom_weight, weights_root, job_id, base_dir, default_weight_path):
    if custom_weight and custom_weight.filename:
        weight_name = secure_filename(custom_weight.filename) or 'custom.pt'
        weight_path = os.path.join(weights_root, f'{job_id}_{weight_name}')
        custom_weight.save(weight_path)
        return weight_path

    if os.path.isabs(default_weight_path):
        return default_weight_path
    return os.path.join(base_dir, default_weight_path)


def parse_training_options(form_data, job_id, parse_int, parse_bool):
    epochs = parse_int(form_data.get('epochs'), 50, minimum=1, maximum=10000)
    batch = parse_int(form_data.get('batch'), 8, minimum=1, maximum=256)
    imgsz = parse_int(form_data.get('imgsz'), 640, minimum=64, maximum=4096)
    device = (form_data.get('device') or 'cpu').strip()
    resume = parse_bool(form_data.get('resume'), default=False)
    run_name = (form_data.get('run_name') or f'finetune-{job_id[:8]}').strip() or f'finetune-{job_id[:8]}'
    return epochs, batch, imgsz, device, resume, run_name


def build_job_payload(job_dataset_dir, yaml_path, weight_path, epochs, batch, imgsz, device, resume, run_name, runs_root):
    return {
        'dataset_dir': job_dataset_dir,
        'yaml_path': yaml_path,
        'weight_path': weight_path,
        'epochs': epochs,
        'batch': batch,
        'imgsz': imgsz,
        'device': device,
        'resume': resume,
        'run_name': run_name,
        'project_dir': runs_root,
    }


def build_job_state(job_id, dataset_name, yaml_relative, run_name, epochs, batch, imgsz, device, resume, now_iso):
    return {
        'job_id': job_id,
        'status': 'queued',
        'created_at': now_iso,
        'started_at': None,
        'completed_at': None,
        'error': None,
        'logs': [],
        'result': {},
        'meta': {
            'dataset_name': dataset_name,
            'yaml_relative': yaml_relative,
            'run_name': run_name,
            'epochs': epochs,
            'batch': batch,
            'imgsz': imgsz,
            'device': device,
            'resume': resume,
        },
    }
