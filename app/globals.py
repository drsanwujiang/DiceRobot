import os

VERSION = "4.7.0"
DEBUG = os.environ.get("DICEROBOT_DEBUG") is not None
DATABASE = os.environ.get("DICEROBOT_DATABASE", os.path.join(os.getcwd(), "database.db"))
LOG_DIR = os.environ.get("DICEROBOT_LOG_DIR", os.path.join(os.getcwd(), "logs"))
LOG_LEVEL = os.environ.get("DICEROBOT_LOG_LEVEL", "INFO")
