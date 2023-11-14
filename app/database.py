from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

from .log import logger
from .models import Base


engine = create_engine("sqlite:///database.db", connect_args={"check_same_thread": False})
Session = sessionmaker(autocommit=False, autoflush=False, bind=engine)


def init_db():
    logger.info("Initializing database")

    engine.connect()
    Base.metadata.create_all(bind=engine)

    logger.info("Database initialized")


def clean_db():
    logger.info("Cleaning database")

    engine.dispose()

    logger.info("Database cleaned")
