from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

from .models import Base


engine = create_engine("sqlite:///database.db", connect_args={"check_same_thread": False})
Session = sessionmaker(autocommit=False, autoflush=False, bind=engine)


def init_database() -> None:
    engine.connect()
    Base.metadata.create_all(bind=engine)


def clean_database() -> None:
    engine.dispose()
